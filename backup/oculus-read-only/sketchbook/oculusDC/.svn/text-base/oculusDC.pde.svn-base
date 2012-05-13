#include <Servo.h>

/*
ASCII Serial Commands
All 2 byte pairs, except for STOP, GET_VERSION, and CAMRELEASE

FORWARD = 'f', [0-255] (speed) 
BACKWARD = 'b', [0-255] (speed)
LEFT = 'l', [0-255] (speed)
RIGHT = 'r', [0-255] (speed)
COMP = 'c', [0 - 255] (DC motor comp: <128 is left, 128 is none, >128 is right)
CAM = 'v', [0-255] (servo angle)  
ECHO_ON = 'e', '1' (echo command back TRUE)
ECHO_OFF = 'e', '0' (echo command back FALSE)
STOP = 's' (DC motors stop)
GET_VERSION = 'y'
CAMRELEASE = 'w'
DIRECT DIFFERENTIAL STEERING = 'm', [0-255][0-255] (speed motor L&R, <128 is back, 128 is stop, >128 is fwd)
*/

// pins
const int motorA1Pin = 4;    // H-bridge pin 2         LEFT motor
const int motorA2Pin = 2;    // H-bridge pin 7         LEFT motor
const int motorB1Pin = 7;    // H-bridge pin 10        RIGHT motor
const int motorB2Pin = 8;    // H-bridge pin 15        RIGHT motor
const int enablePinA = 3;    // H-bridge enable pin 9  LEFT motor
const int enablePinB = 11;   // H-bridge enable pin 1  RIGHT motor
const int camservopin = 6;  

Servo camservo; // tilt

// DC motor compensation 
int acomp = 0;
int bcomp = 0;

boolean echo = false;

// buffer the command in byte buffer 
const int MAX_BUFFER = 8;
int buffer[MAX_BUFFER];
int commandSize = 0;
unsigned long lastcmd = 0;
int timeout = 6000;

void setup() 
{ 
  pinMode(motorA1Pin, OUTPUT); 
  pinMode(motorA2Pin, OUTPUT); 
  pinMode(enablePinA, OUTPUT);
  pinMode(motorB1Pin, OUTPUT); 
  pinMode(motorB2Pin, OUTPUT); 
  pinMode(enablePinB, OUTPUT);
  TCCR2A = _BV(COM2A1) | _BV(COM2B1) | _BV(WGM20); // phase correct (1/2 freq)
  //TCCR2A = _BV(COM2A1) | _BV(COM2B1) | _BV(WGM21) | _BV(WGM20); // 'fast pwm' (1x freq)
  TCCR2B = _BV(CS22) | _BV(CS21) | _BV(CS20); // divide by 1024 
  //TCCR2B = _BV(CS22) | _BV(CS20); // divide by 128 
  //TCCR2B = _BV(CS21) | _BV(CS20); // divide by 8 
  OCR2A = 0;
  OCR2B = 0;

  Serial.begin(115200);
  Serial.println("<reset>");
  lastcmd = millis();
}

void loop() 
{
  if( Serial.available() > 0 )
  {
    // commands take priority 
    lastcmd = millis();
    manageCommand(); 
  } 
  if (millis() - lastcmd > timeout) 
  { 
  			// if no comm with host, stop motors
        lastcmd = millis();
        OCR2A = 0;
        OCR2B = 0;
        camservo.detach();
  }
}

// buffer and/or execute commands from host controller 
void manageCommand()
{
  int input = Serial.read();

  // end of command -> exec buffered commands 
  if((input == 13) || (input == 10))
  {
    if(commandSize > 0)
    {
      parseCommand();
      commandSize = 0; 
    }
  } 
  else 
  {
    // buffer it 
    buffer[commandSize++] = input;

    // protect buffer
    if(commandSize >= MAX_BUFFER)
    {
      commandSize = 0;
    }
  }
}

// do multi byte 
void parseCommand()
{
  if (buffer[0] == 'm')
  {
    int mB = buffer[1]&255;
    int mA = buffer[2]&255;
    
    OCR2A =  (mB&127)<<1;
    OCR2B =  (mA&127)<<1;
    
    if (mB<128)
    {
      digitalWrite(motorB1Pin, LOW);  
      digitalWrite(motorB2Pin, HIGH); 
    }
    else
    {
      digitalWrite(motorB1Pin, HIGH);   
      digitalWrite(motorB2Pin, LOW);  
    }

    if (mA<128)
    {
      digitalWrite(motorA1Pin, LOW);  
      digitalWrite(motorA2Pin, HIGH); 
    }
    else
    {
      digitalWrite(motorA1Pin, HIGH);   
      digitalWrite(motorA2Pin, LOW);  
    }
    
    return;
  }

  // always set speed on each move command 
  if((buffer[0] == 'f') || (buffer[0] == 'b') || (buffer[0] == 'l') || (buffer[0] == 'r'))
  {
    OCR2A =  buffer[1] - acomp*( (float) buffer[1] / 254.0);
    OCR2B =  buffer[1] - bcomp*( (float) buffer[1] / 254.0);    
    // Serial.println("<speed " + (String)buffer[1] + ">");
  } 

  if (buffer[0] == 'f') 
  { // forward
    digitalWrite(motorA1Pin, HIGH);   
    digitalWrite(motorA2Pin, LOW);  
    digitalWrite(motorB1Pin, HIGH); 
    digitalWrite(motorB2Pin, LOW);
  }
  else if (buffer[0] == 'b') 
  { // backward
    digitalWrite(motorA1Pin, LOW);  
    digitalWrite(motorA2Pin, HIGH); 
    digitalWrite(motorB1Pin, LOW);  
    digitalWrite(motorB2Pin, HIGH);
  }
  else if (buffer[0] == 'r') 
  { // right
    digitalWrite(motorA1Pin, HIGH);   
    digitalWrite(motorA2Pin, LOW); 
    digitalWrite(motorB1Pin, LOW);  
    digitalWrite(motorB2Pin, HIGH);
  }
  else if (buffer[0] == 'l') 
  { // left
    digitalWrite(motorA1Pin, LOW);  
    digitalWrite(motorA2Pin, HIGH); 
    digitalWrite(motorB1Pin, HIGH); 
    digitalWrite(motorB2Pin, LOW);
  } 
  else
  if(buffer[0] == 'x')
  {
    Serial.println("<id:oculusDC>");
  }   
  else 
  if(buffer[0] == 'y')
  {
    Serial.println("<version:0.5.4>"); 
  }   
  else 
  if (buffer[0] == 's') 
  { // stop
    OCR2A = 0;
    OCR2B = 0;
  }
  else 
  if(buffer[0] == 'v')
  { // camtilt
    camservo.attach(camservopin);
    camservo.write(buffer[1]);
  }
  else if(buffer[0]== 'w')
  { // camrelease
    camservo.detach();
  }
  else 
  if(buffer[0] == 'c')
  {
    // 128 = 0, > 128 = acomp, < 128 = bcomp
    if (buffer[1] == 128) 
    {
      acomp = 0;
      bcomp = 0;
    }
    if (buffer[1] > 128) 
    {
      bcomp = 0;
      acomp = (buffer[1]-128)*2;
    }
    if (buffer[1] < 128) 
    {
      acomp = 0;
      bcomp = (128-buffer[1])*2;
    }
  } 
  else 
  if(buffer[0] == 'e')
  {
    if(buffer[1] == '1')
      echo = true;
    if(buffer[1] == '0')
      echo = false ;
  } 

  // echo the command back 
  if(echo) 
  { 
    Serial.print("<");
    Serial.print((char)buffer[0]);

    if(commandSize > 1)
      Serial.print(',');    

    for(int b = 1 ; b < commandSize ; b++)
    {
      Serial.print((String)buffer[b]);  
      if(b<(commandSize-1)) 
        Serial.print(',');    
    } 
    Serial.println(">");
 }
}
