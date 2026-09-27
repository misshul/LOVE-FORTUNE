# Frozen location/timezone reference

Authority: [contract](../../location-timezone-reference-v1.md).129 public locations; two validation-only locations;23 timezone zones. No runtime code is included. Manifest/registry are explicit immutable build inputs. Never rerun a population query to assign IDs.

Offline verification from repository root:

```powershell
node docs/contracts/validation/location-timezone.cjs
docker run --rm --network none --entrypoint php --mount "type=bind,source=$PWD,target=/repo,readonly" wordpress:7.1.0-php8.3-apache /repo/docs/contracts/validation/location-timezone-php.php /repo/docs/contracts/references/location-timezone-v1
```

The JS and PHP validators independently parse exact longitudes and compare to frozen Python expectations for all129 selectable locations, plus signed half ties and invalid precision. JS verifies pinned source hashes, artifact/record hashes, manifest/registry consistency, exclusions, aliases, interval ordering, coverage bounds, and explicit known/gap/fold/skip/historical Goldens. These tests do not implement or certify production Natal resolution.

Rebuild outside repository, with two distinct output directories. Use the recorded image digest, not an updated tag. Mount this reference directory read-only at /input and an empty temporary directory writable at /output:

```text
docker run --rm --network none --mount type=bind,source=<ABS_REFERENCE_DIR>,target=/input,readonly --mount type=bind,source=<ABS_TEMP_OUTPUT>,target=/output python@sha256:47ae396f09c1303b8653019811a8498470603d7ffefc29cb07c88f1f8cb3d19f python /input/build-reference.py /input /output
```

Compare the six files listed in replay-validation.json byte-for-byte across both builds and against the packaged files. Preserve explicit manifest/registry/source notices/Goldens as inputs, never regenerate Goldens from new production output. build-reference.py also produces work-source/work-tzif in the external output directory; those are build intermediates, not repository artifacts. It verifies frozen source hashes and reads the explicit129 IDs; it never allocates new IDs.

Build-time tools: Python3.12.11, zic/zdump Debian GLIBC2.41-12. Exact binary hashes are in timezone-manifest.json. Each build compares all23 transition tables with Python ZoneInfo reading the same frozen compiled TZif, daily across1899..2100 and at each transition boundary:1,706,953 probes. This checks extraction/expansion, not independent historical truth. Fixed known Goldens supply separate expected cases.

source-manifest.json identifies full archived inputs. location-manifest.json contains all129 city records, all47 Japanese prefectural-capital IDs/admin1 mappings, and every P2 exclusion. location-registry.json preserves service identity independently of artifact ordering.005/006 are reserved non-public; lookup must not expose them. Final artifacts do not contain P3 locations even though the source archive contains global records.
