/**
 * @file jigsaw.js
 * @author Peter Hanely hanelyp@gmail.com
 * @copyright 2014
 * @license GPL 2+
 */
function Sprite(params, image, display)
{
	this.image = image;
	this.display = display;

	this.index = Sprite.prototype.index++;
	this.sprite = null;
	this.elevate = 0;

	if (display)
	{
		var parent = document.getElementById(display);
		//console.log(parent);
		if (parent == null)	{ parent = display;	}
		//console.log (display, parent);
		this.sprite = document.createElement("div");
		this.sprite.style.position = 'absolute';
		this.sprite.id = 'Sprite_'+this.index;
		this.id = this.sprite.id;
		this.sprite.style.transformOrigin = '50% 50%';
		parent.appendChild(this.sprite);
	}

	this.width = 1*params.width;
	this.height = 1*params.height;
	this.sourceX = 1*params.sourceX || 0;
	this.sourceY = 1*params.sourceY || 0;
	// sprites with framesets and rendered for directions (as used by Xtux)
	this.sprite.style.width = this.width+'px';
	this.sprite.style.height = this.height+'px';
	this.sprite.style.overflow = 'hidden';

	this.setImage(image);
	this.translateTo(1*params.x || 0, 1*params.y || 0);
}

Sprite.prototype = {
	index : 0,	// index of next sprite to generate

	/**
	* move sprite center to x,y
	@arg {number}	x
	@arg {number}	y
	*/
	translateTo : function(x,y)
	{
		this.x = x;// || this.x;
		this.y = y;// || this.y;
		//console.log(x,y);
		
		this.doTranslate();
	},

	/**
	* change image sprite sheet
	* this is slow, not a preferred animation method
	@arg {url} img
	*/
	setImage : function(img)
	{
		this.image = img;
		this.sprite.style.background = 'url('+this.image+') '+
						(-(this.sourceX))+'px '+
						(-(this.sourceY))+'px';
	},

	doTranslate : function()
	{
		if (!this.sprite)	{ return;	}
		this.sprite.style.zIndex = Math.floor(10*(this.y+this.elevate*this.height));
		this.sprite.style.left = (this.x-this.width/2)+'px';
		this.sprite.style.top = (this.y-this.height/2)+'px';
	}
};

puzzle = {
	puzzle:'',
	size:[1,1],
	imgsize:[64,64],
	tiles:[],	// all tiles, ordered by initial position
	tileindex:[],	// tileindex[x+y*sizeY]
	currenttile:-1,
	pickup:[0,0],
	
	init:function(puz, siz, imgsz)
	{
		puzzle.puzzle = puz;
		puzzle.size = siz;
		puzzle.imgsize = imgsz;
		
		puzzle.div = document.getElementById('puzzleframe');
		var tile, index=0;
		for (j = 0; j < siz[1]; j++)
		{
			for (i = 0; i < siz[0]; i++)
			{
				puzzle.tiles.push(tile = new Sprite({x:(i+0.5)*imgsz[0], y:(j+0.5)*imgsz[1], width:imgsz[0], height:imgsz[1], sourceX:i*imgsz[0], sourceY:j*imgsz[1]},
						puz, 'puzzleframe'));
				puzzle.tileindex.push(index++);
			}
		}
		puzzle.div.onclick = puzzle.click;
		puzzle.div.onmouseout = puzzle.drop;	// drop if mouse gets too far
		puzzle.div.ontouch = puzzle.click;	// touchscreen equivilent
		puzzle.div.onmousemove = puzzle.drag;
	},
	
	click:function(e)
	{
		// find which tile, if any, is under current location, pickup
		var position = puzzle.div.getBoundingClientRect();
		var pu = [e.clientX-position.left, e.clientY-position.top];
		//console.log(pu);
		var X = Math.floor(pu[0]/puzzle.imgsize[0]);
		var Y = Math.floor(pu[1]/puzzle.imgsize[1]);
		
		var slotoffset = 0;
		// clip vertically
		if (Y < 0)	{ Y = 0;	slotoffset = 100;	}
		if (Y >= puzzle.size[1])	{ Y = puzzle.size[1]-1;	slotoffset = 100;	}
		if ((X < 0) || (X >= puzzle.size[0]))	{	slotoffset = 100;	}
		
		var slot = X+Y*puzzle.size[0] + slotoffset;
		var next = puzzle.tileindex[slot];
		//console.log(X, Y, next);
		puzzle.pickup = [pu[0]-puzzle.imgsize[0]*X, pu[1]-puzzle.imgsize[1]*Y];	// save tile relative position
		// drop current tile and align
		if (puzzle.currenttile >= 0)
		{
			puzzle.tiles[puzzle.currenttile].elevate = 0;
			puzzle.tiles[puzzle.currenttile].translateTo(puzzle.imgsize[0]*(X+0.5), puzzle.imgsize[1]*(Y+0.5));
			puzzle.tiles[puzzle.currenttile].sprite.className="tileunSelected";
		}
		puzzle.tileindex[slot] = puzzle.currenttile;
		
		// record picked up tile
		puzzle.currenttile = next;

		// activate new selection
		if (puzzle.currenttile >= 0)
		{
			puzzle.tiles[puzzle.currenttile].sprite.className="tileSelected";
			puzzle.tiles[puzzle.currenttile].elevate = 10;
			puzzle.tiles[puzzle.currenttile].translateTo(puzzle.imgsize[0]*(X+0.95), puzzle.imgsize[1]*(Y+0.95));
		}
		document.getElementById("confirm_code").value = puzzle.tileindex.slice(0,9).join(',');
	},
	
	drop:function(e)
	{
		var position = puzzle.div.getBoundingClientRect();
		var pu = [e.clientX-position.left, e.clientY-position.top];
		var Y = Math.floor(pu[1]/puzzle.imgsize[1]);
		if ((puzzle.currenttile >= 0) && ((Y < 0) || (Y >= puzzle.size[1])) )	{	puzzle.click(e);	}
	},
	
	drag:function(e)
	{
		if (puzzle.currenttile >=0)
		{
			var position = puzzle.div.getBoundingClientRect();
			var pu = [e.clientX-position.left, e.clientY-position.top];
			puzzle.tiles[puzzle.currenttile].elevate = 10;
			puzzle.tiles[puzzle.currenttile].translateTo(puzzle.imgsize[0]*0.5+pu[0]-puzzle.pickup[0], puzzle.imgsize[1]*0.5+pu[1]-puzzle.pickup[1]);
		}
	}
};

