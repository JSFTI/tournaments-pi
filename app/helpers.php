<?php
if (!function_exists('roundUpToPowerOfTwo')){
  function roundUpToPowerOfTwo($x){
      return pow(2, floor(log($x, 2)) + 1);
  }
}

if (!function_exists('roundDownToPowerOfTwo')){
  function roundDownToPowerOfTwo($x){
    return pow(2, ceil(log($x, 2)));
  }
}

if(!function_exists('calculateByes')){
  function calculateByes($n){
    if($n == 2 || $n == 4 ) {
      return 0;
    }
    $nearestPowerOfTwo = roundDownToPowerOfTwo($n);
    return $n === $nearestPowerOfTwo ? 0 : $nearestPowerOfTwo - $n;
  }
}

if(!function_exists('calculateMaxRounds')){
  function calculateMaxRounds($n){
    $maxPlayers = roundUpToPowerOfTwo($n);
    
  }
}
?>