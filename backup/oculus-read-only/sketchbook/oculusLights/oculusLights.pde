/*
ocuLED_Light


 ASCII Serial Commands
 GET_VERSION = 'y'

 */

const int lightPinA = 3;
const int lightPinB = 11;
const int dockLightPin = 5;

unsigned long lastcmd = 0;
int timeout = 720000;

void setup() {
	pinMode(lightPinA, OUTPUT);
	pinMode(lightPinB, OUTPUT);
	pinMode(dockLightPin, OUTPUT);

	//overide default PWM freq
	TCCR2A = _BV(COM2A1) | _BV(COM2B1) | _BV(WGM20); // phase correct (1/2 freq)
	//TCCR2A = _BV(COM2A1) | _BV(COM2B1) | _BV(WGM21) | _BV(WGM20); // 'fast pwm' (1x freq)
	//TCCR2B = _BV(CS22) | _BV(CS21) | _BV(CS20); // divide by 1024
	TCCR2B = _BV(CS22) | _BV(CS20); // divide by 128
	//TCCR2B = _BV(CS21) | _BV(CS20); // divide by 8
	OCR2A = 0;
	OCR2B = 0;

	Serial.begin(57600);
	Serial.print('R');
}

void loop() {
	int input = 0;
	if( Serial.available() > 0 ){
		input = Serial.read();
		parseCommand(input);
		lastcmd = millis();
	}

	if (millis() - lastcmd > timeout) {
		// if no comm with host, stop motors
		lastcmd = millis();
		OCR2A = 0;
		OCR2B = 0;
		digitalWrite(dockLightPin, LOW);
	}
}

void parseCommand(int cmd){

 if(cmd == 'x'){
   Serial.print('L');
   return;
 }

 if(cmd == 'y'){
   Serial.print('1');
   return;
 }

 if(cmd == 'f'){
   digitalWrite(dockLightPin, LOW);
   Serial.print(cmd);
   return;
 }

 if(cmd == 'o'){
   digitalWrite(dockLightPin, HIGH);
   Serial.print(cmd);
   return;
 }

 if(cmd == 'a'){
   OCR2A = 0;
   OCR2B = 0;
 }
 else if(cmd == 'b'){
   OCR2A = 80;
   OCR2B = 80;
 }
 else if(cmd == 'c'){
   OCR2A = 100;
   OCR2B = 100;
 }
 else if(cmd == 'd'){
   OCR2A = 120;
   OCR2B = 120;
 }
 else if(cmd == 'e'){
   OCR2A = 140;
   OCR2B = 140;
 }
 else if(cmd == 'f'){
   OCR2A = 160;
   OCR2B = 160;
 }
 else if(cmd == 'g'){
   OCR2A = 180;
   OCR2B = 180;
 }
 else if(cmd == 'h'){
   OCR2A = 200;
   OCR2B = 200;
 }
 else if(cmd == 'i'){
   OCR2A = 220;
   OCR2B = 220;
 }
 else if(cmd == 'j'){
   OCR2A = 240;
   OCR2B = 240;
 }
 else if(cmd == 'k'){
   OCR2A = 255;
   OCR2B = 255;
 }

 Serial.print(cmd);
}