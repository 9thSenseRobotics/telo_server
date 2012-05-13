package oculus
{
	//	import flash.display.BitmapData;
	import flash.utils.ByteArray;
	
	public class OculusImage 
	{
		private var parr:Array = []; // working pixels, whole image, 8-bit greyscale 
		private var width:int;
		private var height:int;
		public var lastThreshhold:int;
		private var threshholdMult:Number;  //  = 0.65;
		private var lastBlobRatio:Number;
		private var lastTopRatio:Number;
		private var lastBottomRatio:Number;
		private var lastMidRatio:Number;
		private var parrorig:Array;
		private var imgaverage:int;
		
		public function OculusImage()
		{
		}
		
		public function dockSettings(str:String):void {
			var a:Array = str.split("_");
			lastBlobRatio = a[0];
			lastTopRatio = a[1];
			lastMidRatio = a[2];
			lastBottomRatio = a[3];
		}
		
		public function convertToGrey(pixelRGB:ByteArray):void {
			// uses 30-59-11 RGB weighting from: http://en.wikipedia.org/wiki/Grayscale#Converting_color_to_grayscale
			var p:int;
			parr = [];
			var n:int = 0;
			var runningttl:int = 0;			
			for (var i:int=0; i < pixelRGB.length; i+=4) {
				p = pixelRGB[i+1]*0.3 + pixelRGB[i+2]*0.59 + pixelRGB[i+3]*0.11 ;
				parr[n]=p;
				n++;
				runningttl += p;
			}
			imgaverage = runningttl/n;
			threshholdMult = 0.65 - 0.2 + (0.40*( imgaverage/255));
		}
		
		private function floodFill(ablob:Array, start:int):Array {  
			// from http://en.wikipedia.org/wiki/Flood_fill
			var q:Array = [start];
			var blob:Array = [];
			var n:int;
			var w:int;
			var e:int;
			var i:int;
			while (q.length > 0) {
				n = q.pop();
				if (ablob[n]) {
					w = n;
					e = n;
					while (ablob[w]) { w-=1; }
					while (ablob[e]) { e+=1; }
					for (i=w+1; i<=e-1; i++) { 
						ablob[i]=false; 
						blob[i]=true;
						if (ablob[i-width]) { q.push(i-width); }
						if (ablob[i+width]) { q.push(i+width); }
					}
				}
			}
			return blob;
		}
		
		public function findBlobStart(x:int, y:int, w:int, h:int, bar:ByteArray):Array { // calibrate only...
			lastThreshhold = 0;
			var r:Array;
			findBlobStartSub(x,y,w,h,bar);
			r = findBlobStartSub(x,y,w,h,bar); // do it again, with contrast averaged
			return r;
		}
		
		public function findBlobStartSub(x:int, y:int, w:int, h:int, bar:ByteArray):Array { // calibrate sub
			width = w;
			height = h;
			convertToGrey(bar);
			parrorig = parr.slice(); // save original image for re-threshholding after
			var start:int = x + y*width; 
			var result:Array = [0,0,0,0,0];
			
			var startavg:int = (parr[start-1]+parr[start]+parr[start+1])/3; //includes 2 adjacent pixels in contract threshhold to counteract grainyness a bit
			var threshhold:int = startavg*threshholdMult;
			// var threshhold:int = parr[start]*threshholdMult;
			
			if (lastThreshhold !=0) {
				threshhold = lastThreshhold;
			}
			var i:int;
			for (i=0;i<parr.length;i++){
				if (parr[i]>threshhold) { parr[i]=true; }
				else { parr[i]=false; }
			}
			var blob:Array = floodFill(parr, start);
			if (blob.length>1) { // error, its always greater than 1!!  should be 'contains at least one TRUE'? or just remove condition
				var r:Array = getRect(blob,start);
				var minx:int = r[0];
				var maxx:int = r[1];
				var miny:int = r[2];
				var maxy:int = r[3];  
				var blobSize:int = r[4];
				var	blobBox:int = (maxx-minx)*(maxy-miny);
				/*
				lastTopRatio = getPixelEqTrueCount(blob, minx, maxx, miny, miny+(maxy-miny)*0.333) / blobBox;
				lastBottomRatio = getPixelEqTrueCount(blob, minx, maxx, miny+(maxy-miny)*0.666, maxy) / blobBox;
				lastMidRatio = getPixelEqTrueCount(blob,minx,maxx,miny+(maxy-miny)*0.333, miny+(maxy-miny)*0.666) / blobBox;
				*/
				lastTopRatio = getPixelEqTrueCount(blob, minx, minx+(maxx-minx)*0.333, miny, maxy) / blobBox; // left
				lastMidRatio = getPixelEqTrueCount(blob,minx+(maxx-minx)*0.333,minx+(maxx-minx)*0.666, miny, maxy) / blobBox;
				lastBottomRatio = getPixelEqTrueCount(blob, minx+(maxx-minx)*0.666, maxx, miny, maxy) / blobBox; // left
				lastBlobRatio = (maxx-minx)/(maxy-miny);
				var slope:Number =  getBottomSlope(blob,minx,maxx,miny,maxy);
				//result = x,y,width,height,,slope,lastBlobRatio,lastTopRatio,lastMidRatio,lastBottomRatio
				result = [minx,miny,maxx-minx,maxy-miny,slope,lastBlobRatio,lastTopRatio,lastMidRatio,lastBottomRatio]; 
			}
			if (lastThreshhold==0) {
				var runningttl:int = 0;
				for (i=0; i<=width*height; i++) { // zero to end
					if (blob[i]) {
						runningttl += parrorig[i];
					}
				}
				lastThreshhold = (runningttl/blobSize)*threshholdMult; // adaptive threshhold
			}
			return result;
		}
		
		public function getThreshholdxy(x:int, y:int, w:int, h:int, bar:ByteArray):Array { // unused
			width = w;
			height = h;
			convertToGrey(bar);
			var start:int = x + y*width; 
			var result:Array = [0,0,0,0,0];
			var startavg:int = (parr[start-1]+parr[start]+parr[start+1])/3; //includes 2 adjacent pixels in contract threshhold to counteract grainyness a bit
			var threshhold:int = startavg*threshholdMult;
			lastThreshhold = threshhold;
			var i:int;
			var parrinv:Array = []; // inverse, used to check for inner black blob
			for (i=0; i<parr.length; i++){
				if (parr[i]>threshhold) { 
					parr[i]=true;
					parrinv[i]=false;
				}
				else { 
					parr[i]=false;
					parrinv[i]=true; 
				}
			}			
			var blob:Array = floodFill(parr, start);
			var r:Array = getRect(blob,start);
			var minx:int = r[0];
			var maxx:int = r[1];
			var miny:int = r[2];
			var maxy:int = r[3];
			var blobSize:int = r[4];
			
			var xx:int = minx+((maxx-minx)/2);
			var yy:int = miny+((maxy-miny)/2);
			i = xx + yy*width;  // dead center of winner blob
			var ctrblob:Array = floodFill(parrinv,i);
			var rctr:Array = getRect(ctrblob,i);
			if (minx<rctr[0] && maxx>rctr[1] && miny<rctr[2] && maxy>rctr[3] && ctrblob.length > 1 && rctr[4]<r[4]*0.5) { // && parrinv[i]==true) { // ctrblob completely within blob 
				var slope:Number =  getBottomSlope(blob,minx,maxx,miny,maxy);
				//result = x,y,width,height,slope
				result = [minx,miny,maxx-minx,maxy-miny,slope]; 			
			}
			return result;
		}
		
		public function findBlobs(bar:ByteArray, w:int, h:int):Array {
			var attemptnum:int = 0;
			var dir:int = -1;
			var inc:int = 10;
			var n:int = inc;
			var deleteddir:int = 0;
			var result:Array;
			//lastThreshhold = 999;
			while (attemptnum < 15) { // was 20 before auto threshhold guessing 
				result = findBlobsSub(bar, w, h);
				if (result[2]==0) {
					if (deleteddir != 0) {
						n = inc;
						if (deleteddir == -1) {
							dir = 1;
						}
						else { dir =-1; }
					}
					else {
						dir = dir*(-1);						
					}
					lastThreshhold = lastThreshhold + (n*dir);
					if (lastThreshhold < 0) {
						dir = 1;
						deleteddir = -1;
						lastThreshhold = n;
					}
					if (lastThreshhold > 255) {
						dir = -1;
						deleteddir = 1;
						lastThreshhold = 255-n;
					}
					n += inc;
				}
				else { break; }
				//trace ("attempt: "+attemptnum+" lastThreshhold: "+lastThreshhold);
				attemptnum ++;
			}
			return result;
		}
		
		public function findBlobsSub(bar:ByteArray, w:int, h:int):Array {
			width = w;
			height = h;
			blobs = [];
			var result:Array = [0,0,0,0,0,0];
			//var imgaverageOld:int = imgaverage;
			convertToGrey(bar);
			parrorig = parr.slice();
			if (lastThreshhold == 999) { lastThreshhold = imgaverage+45; } // auto contrast finding with magical constant
			var threshhold:int = lastThreshhold;
			var i:int;
			var parrinv:Array = []; // inverse, used to check for inner black blob
			for (i=0; i<parr.length; i++){
				if (parr[i]>threshhold) { 
					parr[i]=true;
					parrinv[i]=false;
				}
				else { 
					parr[i]=false;
					parrinv[i]=true; 
				}
			}
			//var allBlobsPixels:Array = parr.slice();  //devel only, normally use parr instead of allBlobsPixels
			var blobnum:int = 0;
			var maxdiff:Number = 99;
			var diff:Number;
			var winner:int =-1;
			// var winnerBlobSize:int; 
			var winRect:Array;
			var winnerTopRatio:Number;
			var winnerBlobRatio:Number;
			var winnerBottomRatio:Number;
			var winnerMidRatio:Number;
			var minx:int;
			var miny:int;
			var maxx:int;
			var maxy:int; 
			var topRatio:Number;
			var blobRatio:Number;
			var bottomRatio:Number;
			var midRatio:Number;
			var blobSize:int;
			var r:Array;
			var pixel:int;
			var blobs:Array = []
			var blobBox:int;
			var blobstarts:Array = [];
			for (pixel=0; pixel<=width*height; pixel++) { // zero to end, find all blobs
				if (parr[pixel]) { // finds a white one >> production uses parr[pixel]
					blobs[blobnum] = floodFill(parr, pixel);
					blobstarts[blobnum]=pixel;
					blobnum++;
				}
			}		
			var rejectedBlobs:Array = [];
			while (rejectedBlobs.length < blobs.length) {
				for (blobnum=0; blobnum<blobs.length; blobnum++) { // go thru and eval each blob
					if (rejectedBlobs.indexOf(blobnum) == -1) {
						r = getRect(blobs[blobnum],blobstarts[blobnum]); 
						blobSize = r[4];
						if (blobSize > 150) { // discard tiny blobs
							minx = r[0];
							maxx = r[1];
							miny = r[2];
							maxy = r[3];  
							blobBox = (maxx-minx)*(maxy-miny);
							topRatio = getPixelEqTrueCount(blobs[blobnum], minx, minx+(maxx-minx)*0.333, miny, maxy) / blobBox; // left
							midRatio = getPixelEqTrueCount(blobs[blobnum],minx+(maxx-minx)*0.333,minx+(maxx-minx)*0.666, miny, maxy) / blobBox;
							bottomRatio = getPixelEqTrueCount(blobs[blobnum], minx+(maxx-minx)*0.666, maxx, miny, maxy) / blobBox; // left
							blobRatio = (maxx-minx)/(maxy-miny);
							diff = Math.abs(topRatio - lastTopRatio) + Math.abs(bottomRatio- lastBottomRatio) + Math.abs(midRatio- lastMidRatio);
							if (diff < maxdiff) { // && diff < 1.1 && blobRatio < lastBlobRatio * 1.2 && blobRatio > lastBlobRatio * 0.5) {
								//if (diff < maxdiff && blobRatio < lastBlobRatio * 1.1) {
								winner=blobnum;
								// winnerBlobSize = r[4];
								maxdiff = diff;
								winRect = r.slice();
								winnerTopRatio = topRatio;
								winnerBottomRatio = bottomRatio;
								winnerMidRatio = midRatio;
								winnerBlobRatio = blobRatio;
							}
						} //size condition end bracket
					}
				}
				if (winner == -1) { break; }
				else { // best looking blob chosen, now check if it has ctr blob
					minx = winRect[0];
					maxx = winRect[1];
					miny = winRect[2];
					maxy = winRect[3];
					var x:int = minx+((maxx-minx)/2);
					var y:int = miny+((maxy-miny)/2);
					i = x + y*width;  // dead center of winner blob
					var ctrblob:Array = floodFill(parrinv,i);
					r = getRect(ctrblob,i);
					if (minx<r[0] && maxx>r[1] && miny<r[2] && maxy>r[3] && ctrblob.length > 1 && r[4]<winRect[4]*0.5) { // && parrinv[i]==true) { // ctrblob completely within blob 
						break;
					}
					else {
						//trace("rejected: "+winner);
						rejectedBlobs.push(winner);
						winner = -1;
					}
				}
			}
			
			if (winner != -1) {
				//Lower 3rd, left
				/*
				lastTopRatio = winnerTopRatio; 
				lastBottomRatio = winnerBottomRatio;
				lastMidRatio = winnerMidRatio;
				lastBlobRatio = winnerBlobRatio;
				*/  
				var slope:Number =  getBottomSlope(blobs[winner],minx,maxx,miny,maxy);
				result = [minx,miny,maxx-minx,maxy-miny,slope]; //x,y,width,height,slope
				
				blobSize = winRect[4];		
				var runningttl:int = 0;
				for (pixel=0; pixel<=width*height; pixel++) { // zero to end
					if (blobs[winner][pixel]) {
						runningttl += parrorig[pixel];
					}
				}
				lastThreshhold = (runningttl/blobSize)*threshholdMult; // adaptive threshhold
			}
			return result;
		}
		
		private function getRect(blob:Array, start:int):Array {
			var y:int = start/width;
			var x:int = start - (y*width); 	
			var minx:int = x;
			var miny:int = y;
			var maxx:int = x;
			var maxy:int = y;
			var p:int;
			var tempy:int 
			var tempx:int
			var size:int = 0;
			for (p=0; p<width*height; p++) {
				if (blob[p]) {
					tempy = p/width;
					tempx = p - (tempy*width); 
					if (tempx < minx) { minx = tempx; }
					if (tempx > maxx) { maxx = tempx; }
					if (tempy < miny) { miny = tempy; }
					if (tempy > maxy) { maxy = tempy; }
					size++;
				}
			}
			var result:Array=[minx,maxx,miny,maxy,size];
			return result;
		}
		
		private function getPixelEqTrueCount(blob:Array,startx:int,endx:int,starty:int,endy:int):int {
			var result:int = 0;
			for (var yy:int = starty; yy<endy; yy++) {
				for (var xx:int = startx; xx<=endx; xx++) {
					if (blob[yy*width + xx]) {
						result++;
					}
				}
			}
			return result;
		}
		
		private function getBottomSlope(blob:Array, minx:int, maxx:int, miny:int, maxy:int):Number {
			var start:int = -1;
			for (var i:int = maxx+maxy*width; i>=minx+miny*width; i-=1) {
				if (blob[i]) {
					start = i;
					break;
				}
			}
			var starty:int = start/width;
			var startx:int = start-(starty*width);
			var direction:int = 1;
			if (startx > minx+(maxx-minx)/2) { direction = -1; }
			if (direction == -1) {
				while (blob[start+1]) { start ++; }
			}
			else {
				while (blob[start-1]) { start -= 1; }
			}
			var end:int = start;
			while (blob[end + direction] || blob[end-width+direction]) { //crawl up diagonally or flat until hit vert wall
				end += direction;
				if (!blob[end]) { end -= width; }
			}
			var endy:int = end/width;
			var endx:int = end-(endy*width);
			//trace("slope function: "+startx+" "+starty+" "+endx+" "+endy+" "+start);
			return (endy-starty)/(endx-startx);		
		}
		
		public function edges(argb:ByteArray, width:int, height:int):void {
			convertToGrey(argb);
			var lastparr:Array = parr.slice();
			var n:int=0;
			for each (var pixel:int in lastparr) {
				var darkest:int = parr[n];
				var lightest:int = darkest;
				//var closesteight:Array = [n-width-1, n-width, n-width+1, n-1, n+1, n+width-1, n+width, n+width+1];
				var closesteight:Array = [n-width, n-1, n+1, n+width]; 
				for each (var i:int in closesteight) {
					if (( i >=0 && i <= lastparr.length ) && !((n as Number)/(width as Number) == n/width && i == n+1)
						&&  !(((n-1) as Number)/(width as Number) == n/width && i == n-1)) {
						if (lastparr[i] < darkest) {
							darkest = lastparr[i];
						}
						if (lastparr[i] > lightest) {
							lightest = lastparr[i]
						}
					} 
				}
				if (lightest - darkest > 30) {
					parr[n] = true;
				}
				else  { parr[n] = false; }
				n ++;
			}
		}
		
		public function processedImage():ByteArray {
			var newPixels:ByteArray = new ByteArray();
			var n:int = 0;
			var p:int;
			for each (var i:Boolean in parr) {
				if (i) { p=255; }
				else { p=0; }
				newPixels[n]=255;
				newPixels[n+1]=p;
				newPixels[n+2]=p;
				newPixels[n+3]=p;
				n+=4;
			}
			return newPixels;		
		}
		
	}
}