<?php
declare(strict_types=1);

namespace LoveFortune\Core\Support;

/** Exact, reduced rational. Boundary inputs are decimal/fraction strings, never floats. */
final readonly class Rational implements \Stringable
{
    private function __construct(private string $magnitude, private string $denominator, private int $sign) {}

    public static function of(string|int $value): self
    {
        $value = (string) $value;
        if (!preg_match('/^(-?)([0-9]+)(?:\.([0-9]+)|\/([0-9]+))?$/D', $value, $matches)) {
            throw new \InvalidArgumentException('Expected an exact decimal or fraction.');
        }
        $fraction = $matches[3] ?? '';
        return self::make($matches[2] . $fraction, $matches[4] ?? ('1' . str_repeat('0', strlen($fraction))), $matches[1] === '-' ? -1 : 1);
    }

    private static function make(string $n, string $d, int $sign): self
    {
        $n = DecimalInteger::normalize($n);
        $d = DecimalInteger::normalize($d);
        if ($d === '0') {
            throw new \DivisionByZeroError('Zero denominator.');
        }
        $gcd = DecimalInteger::gcd($n, $d);
        return new self(DecimalInteger::divide($n, $gcd)[0], DecimalInteger::divide($d, $gcd)[0], $n === '0' ? 0 : $sign);
    }

    public function add(self $other): self
    {
        $left = DecimalInteger::multiply($this->magnitude, $other->denominator);
        $right = DecimalInteger::multiply($other->magnitude, $this->denominator);
        $denominator = DecimalInteger::multiply($this->denominator, $other->denominator);
        if ($this->sign === $other->sign) {
            return self::make(DecimalInteger::add($left, $right), $denominator, $this->sign);
        }
        $cmp = DecimalInteger::compare($left, $right);
        return self::make($cmp >= 0 ? DecimalInteger::subtract($left, $right) : DecimalInteger::subtract($right, $left), $denominator, $cmp >= 0 ? $this->sign : $other->sign);
    }

    public function negate(): self { return new self($this->magnitude, $this->denominator, -$this->sign); }
    public function abs(): self { return new self($this->magnitude, $this->denominator, abs($this->sign)); }
    public function subtract(self $other): self { return $this->add($other->negate()); }

    public function multiply(self $other): self
    {
        return self::make(DecimalInteger::multiply($this->magnitude, $other->magnitude), DecimalInteger::multiply($this->denominator, $other->denominator), $this->sign * $other->sign);
    }

    public function divide(self $other): self
    {
        return self::make(DecimalInteger::multiply($this->magnitude, $other->denominator), DecimalInteger::multiply($this->denominator, $other->magnitude), $this->sign * $other->sign);
    }

    public function compare(self $other): int
    {
        return ($this->sign <=> $other->sign) ?: $this->sign * DecimalInteger::compare(DecimalInteger::multiply($this->magnitude, $other->denominator), DecimalInteger::multiply($other->magnitude, $this->denominator));
    }

    public function isZero(): bool { return $this->sign === 0; }

    public function clamp(self $minimum, self $maximum): self
    {
        return $this->compare($minimum) < 0 ? $minimum : ($this->compare($maximum) > 0 ? $maximum : $this);
    }

    /** Serialization boundary only. Signed ties round away from zero; no negative zero. */
    public function halfUp4(): string
    {
        [$quotient, $remainder] = DecimalInteger::divide(DecimalInteger::multiply($this->magnitude, '10000'), $this->denominator);
        if (DecimalInteger::compare(DecimalInteger::multiply($remainder, '2'), $this->denominator) >= 0) {
            $quotient = DecimalInteger::add($quotient, '1');
        }
        $digits = str_pad($quotient, 5, '0', STR_PAD_LEFT);
        return ($this->sign < 0 && $quotient !== '0' ? '-' : '') . substr($digits, 0, -4) . '.' . substr($digits, -4);
    }

    public function __toString(): string
    {
        return ($this->sign < 0 ? '-' : '') . $this->magnitude . '/' . $this->denominator;
    }
}
