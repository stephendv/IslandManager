var DAYSBETWEENSHORTEQ = 10;
var DAYSBETWEENABSBORB = 3;
var PULSEENDAMPS = 2.4;
var ENDAMPS = 2.2;
var MIN_ABSORB_VOLTAGE = 57;


var oneDay = 24*60*60*1000;
var currentDate = new Date();


// Configure the last short EQ date
var daysSinceShortEQ = (currentDate.getTime() - lastShortEQ.value) / oneDay;

// Days since the last Absorb
var daysSinceAbsorb = (currentDate.getTime() - lastAbsorb.value) / oneDay;

// Determine what mode to put go into once a day at 10am
if (currentDate.getHours() == 10) {
   if ((daysSinceShortEQ > DAYSBETWEENSHORTEQ || deepDischarge.value == true) && forecast.value == "clear" && soc.value > 80) {
      mode.set("SHORTEQ");
  } else if (soc.value > 80 && daysSinceAbsorb < DAYSBETWEENABSBORB &&   (forecast.value == 'clear' || forecast.value == 'partlycloudy')) {
     mode.set("FLOAT");
  } else {
      mode.set("ABSORB");
  }
}



// End amps
if (mode.value == "ABSORB") {
    if (stage.value == 3 && current.value > 0 && current.value < ENDAMPS && battVoltage.value > MIN_ABSORB_VOLTAGE) {
        forceFloat.set(true);
        lastAbsorb.set(currentDate.getTime());
    } else {
        forceFloat.set(false);
    } 
}

if (mode.value == "SHORTEQ") {
   if (stage.value == 3 && current.value < PULSEENDAMPS) {
         shortEQ.set(true);
         lastShortEQ.set(currentDate.getTime());
         deepDischarge.set(false);
         mode.set("ABSORB");
   }
} 

if (currentDate.getHours() == 10 && mode.value == "FLOAT" && stage.value != 5 && stage.value != 6 && stage.value != 0) {
     forceFloat.set(true);
}
