<?php

namespace ETNA\FeatureContext;

trait Coverage
{
    /**
     * @param string $prefix
     */
    protected function check($expected_value, $found_value, $prefix, &$errors)
    {
        if (true === is_string($expected_value) && $expected_value === "#Array#") {
            if (false === is_array($found_value)) {
                $errors[] = sprintf("%-35s: not an array", $prefix);
            }

            return;
        }

        if (true === is_string($expected_value) && substr($expected_value, 0, 1) === "#" && substr($expected_value, -1, 1) === "#") {
            if (1 !== preg_match($expected_value, $found_value)) {
                $errors[] = sprintf("%-35s: regex error : '%s' does not match '%s'", $prefix, $found_value, $expected_value);
            }

            return;
        }

        $t1 = gettype($expected_value);
        $t2 = gettype($found_value);
        if ($t1 !== $t2) {
            $errors[] = sprintf("%-35s: type error : expected '%s'; got '%s'", $prefix, $t1, $t2);
            return;
        }

        if (true === is_array($expected_value)) {
            $l1 = count($expected_value);
            $l2 = count($found_value);
            if ($l1 !== $l2) {
                $errors[] = sprintf("%-35s: array length error : expected '%d'; got '%d'", $prefix, $l1, $l2);
                return;
            }

            for ($i = 0 ; $i < $l1; $i++) {
                $this->check($expected_value[$i], $found_value[$i], "{$prefix}[{$i}]", $errors);
            }
            return;
        }

        if (true === is_object($expected_value)) {
            $expected_keys = array_keys((array) $expected_value);
            $found_keys    = array_keys((array) $found_value);

            foreach (array_diff($expected_keys, $found_keys) as $key) {
                $errors[] = sprintf("%-35s: missing key", "{$prefix}->{$key}", $key);
            }
            foreach (array_diff($found_keys, $expected_keys) as $key) {
                $errors[] = sprintf("%-35s: unexpected key", "{$prefix}->{$key}", $key);
            }

            foreach (array_intersect($expected_keys, $found_keys) as $key) {
                $this->check($expected_value->$key, $found_value->$key, "{$prefix}->{$key}", $errors);
            }

            return;
        }

        if ($expected_value !== $found_value) {
            $errors[] = sprintf("%-35s: value error : expected %s; got %s", $prefix, var_export($expected_value, true), var_export($found_value, true));
        }
    }
}
