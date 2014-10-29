<?php

namespace ETNA\FeatureContext;

trait Coverage
{
    /**
     * Valide l'égalité de 2 valeurs ($expected_value & $found_value)
     *
     * Le test prendra en considération les tableaux associatifs qui n'auront pas la nécessisité
     * d'être dans le même ordre, mais devront contenir exactement les mêmes clefs et les mêmes valeurs.
     *
     * Il y a aussi quelques exceptions possibles :
     *  - "key" => "#Array#" : va juste vérifier que la valeur de $expected_value["key"] est bien un tableau
     *  - "key" => #regex# : va vérifier que la valeur de $expected_value["key"] matche la regexp "regexp"
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

        $expected_type = gettype($expected_value);
        $found_type    = gettype($found_value);
        if ($expected_type !== $found_type) {
            $errors[] = sprintf("%-35s: type error : expected '%s'; got '%s'", $prefix, $expected_type, $found_type);
            return;
        }

        if (true === is_array($expected_value)) {
            $expected_count = count($expected_value);
            $found_count    = count($found_value);
            if ($expected_count !== $found_count) {
                $errors[] = sprintf("%-35s: array length error : expected '%d'; got '%d'", $prefix, $expected_count, $found_count);
                return;
            }

            for ($i = 0 ; $i < $expected_count; $i++) {
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
