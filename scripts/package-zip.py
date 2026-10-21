"""Build-only ZIP contract. No runtime dependency; Python standard library only."""
import hashlib
import io
import json
import pathlib
import stat
import sys
import zipfile

ROOT = "love-fortune-core/"
STAMP = (1980, 1, 1, 0, 0, 0)


def entries(files):
    result = {ROOT: None}
    for name, data in files.items():
        parts = name.split("/")
        assert name and all(p not in ("", ".", "..") for p in parts)
        assert "\\" not in name and ":" not in name
        for i in range(1, len(parts)):
            result[ROOT + "/".join(parts[:i]) + "/"] = None
        result[ROOT + name] = data
    return result


def build(files):
    output = io.BytesIO()
    with zipfile.ZipFile(output, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for name, data in sorted(entries(files).items()):
            info = zipfile.ZipInfo(name, STAMP)
            info.create_system = 3  # Unix, independent of build host.
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = ((stat.S_IFDIR | 0o755) << 16 | 0x10) if data is None else ((stat.S_IFREG | 0o644) << 16)
            z.writestr(info, b"" if data is None else data, compresslevel=9)
    return output.getvalue()


def verify(blob, files):
    expected = entries(files)
    with zipfile.ZipFile(io.BytesIO(blob)) as z:
        infos = z.infolist()
        assert len(infos) == len(expected)
        assert set(z.namelist()) == set(expected)
        for info in infos:
            assert "\\" not in info.filename
            assert info.create_system == 3
            mode = info.external_attr >> 16
            directory = expected[info.filename] is None
            assert mode == ((stat.S_IFDIR | 0o755) if directory else (stat.S_IFREG | 0o644))
            assert info.is_dir() == directory
            assert z.read(info) == (b"" if directory else expected[info.filename])
        assert z.testzip() is None
    return {"result": "PASS", "files": len(files), "directories": len(expected) - len(files),
            "backslashEntries": 0, "directoryMode": "0755", "fileMode": "0644"}


def self_test():
    files = {"assets/css/frontend.css": b"css", "src/Infrastructure/Rate/a.php": b"php"}
    blob = build(files)
    verify(blob, files)
    assert blob == build(dict(reversed(list(files.items()))))
    checks = 2
    # Mutate central-directory host, paths and permissions: old broken ZIPs must fail.
    for mutation in ("backslash", "dos", "dir0000", "dir0600", "file0000", "missing"):
        output = io.BytesIO()
        with zipfile.ZipFile(io.BytesIO(blob)) as old, zipfile.ZipFile(output, "w") as new:
            for info in old.infolist():
                data = old.read(info)
                if mutation == "missing" and info.filename == ROOT:
                    continue
                if mutation == "backslash":
                    info.filename = info.filename.replace("/", "\\")
                if mutation == "dos":
                    info.create_system = 0
                if mutation.startswith("dir") and info.is_dir():
                    info.external_attr = (stat.S_IFDIR | int(mutation[3:], 8)) << 16
                if mutation == "file0000" and not info.is_dir():
                    info.external_attr = stat.S_IFREG << 16
                new.writestr(info, data)
        try:
            verify(output.getvalue(), files)
        except AssertionError:
            checks += 1
        else:
            raise AssertionError("Unsafe ZIP accepted: " + mutation)
    for name in ("../escape", "a\\b", "/absolute", "a//b", "C:/file"):
        try:
            build({name: b"x"})
        except AssertionError:
            checks += 1
        else:
            raise AssertionError("Unsafe input accepted")
    print(json.dumps({"result": "ZIP_METADATA_TESTS_PASS", "checks": checks}))


if __name__ == "__main__":
    if sys.argv[1] == "--self-test":
        self_test()
    else:
        base = pathlib.Path(sys.argv[1])
        manifest = json.loads((base / "candidate-sha256.json").read_text())
        files = {name: (base / "candidate/love-fortune-core" / name).read_bytes() for name in manifest}
        assert all(hashlib.sha256(data).hexdigest() == manifest[name] for name, data in files.items())
        blob = build(files)
        result = verify(blob, files)
        assert blob == build(dict(reversed(list(files.items()))))
        (base / "candidate.zip").write_bytes(blob)
        with zipfile.ZipFile(io.BytesIO(blob)) as z:
            z.extractall(base / "extracted")  # Byte comparison only; Linux permission check is separate.
        result["deterministicRebuild"] = "PASS"
        print(json.dumps(result))
