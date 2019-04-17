<?php

function arrayMean($a) {
    return array_sum($a) / count($a);
}

function arrayDiff($a) {
    $diff_array = [];
    for ($i = 0; $i < count($a); $i++) {
        array_push($diff_array,$a[$i+1] - $a[$i]);
    }
}

function arrayCumSum($a) {
    for ($i = 1; $i < count($a); $i++) {
        $a[$i] = $a[$i] + $a[$i-1];
    }
    return $a;
}

function arrayRound($a,$prec) {
    for ($i = 0; $i < count($a); $i++) {
        $a[$i] = round($a[$i],$prec);
    }
    return $a;
}

function addScalar($a,$s) {
    for ($i = 0; $i < count($a); $i++) {
        $a[$i] = $a[$i] + $s;
    }
    return $a;
}

function substractScalar($a,$s) {
    for ($i = 0; $i < count($a); $i++) {
        $a[$i] = $a[$i] - $s;
    }
    return $a;
}

function arrayAbs($a) {
    for ($i = 0; $i < count($a); $i++) {
        $a[$i] = abs($a[$i]);
    }
    return $a;
}

function lininterp($x, $y, $x_sample) {
    $nx = count($x);
    $ny = count($y);
    if ($nx !== $ny) {
        throw(new Exception("lininterp: x (n = $nx) and y (n = $ny) must have the same number of elements", 500));
    }

    if ($x_sample < $x[0] || $x_sample > $x[$nx-1]) {
        return null;
    }

    $d = arrayAbs(substractScalar($x, $x_sample));
    $imin = array_search(min($d), $d);

    if ($imin === 0) { // first element
        $y_interp = $y[0];
    } elseif ($imin === count($x) - 1) { // last element
        $y_interp = $y[$imin];
    } else {
        if ($x_sample < $x[$imin]) {
            $imin1 = $imin - 1;
            $imin2 = $imin;
        } elseif ($x_sample > $x[$imin]) {
            $imin1 = $imin;
            $imin2 = $imin + 1;
        } else {
            return $x_sample;
        }
        $delta_x = $x[$imin2] - $x[$imin1];
        $r = ($x_sample - $x[$imin1]) / $delta_x;
        $delta_y = $y[$imin2] - $y[$imin1];
        $y_interp = $y[$imin1] + $r * $delta_y;
    }
    return $y_interp;
}

function findNearestIndex($x, $value) {
    $d = arrayAbs(substractScalar($x, $value));
    $imin = array_search(min($d), $d);
}

function smooth1d($x,$k) {
  $n = count($x);

  if (fmod($k,2) == 0) {
    $k = $k + 1;
  }
  $d = ($k-1)/2;

  $xsmooth = $x;
  for ($i = 0; $i < count($x); $i++) {
    $v = 0;
    $wstart = max($i-$d,0);
    $wend = min($i+$d,$n-1);
    for ($w = $wstart; $w <= $wend; $w++) {
      $v = $v + $x[$w];
    }
    // echo "<br> Setting element ".$i." to value ".$v/($wend - $wstart);
    $xsmooth[$i] = $v/($wend - $wstart);
  }
  return $xsmooth;
}
?>