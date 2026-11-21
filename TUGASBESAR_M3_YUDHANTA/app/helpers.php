<?php

if (! function_exists('rupiah')) {
    /** Format angka menjadi rupiah, mis. 250000 -> "Rp250.000". */
    function rupiah(float|int|string|null $nilai): string
    {
        return 'Rp'.number_format((float) $nilai, 0, ',', '.');
    }
}
