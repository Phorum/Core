var NS=1;
var MZ=2;
var IE=3;

var HID='hidden';
var VIS='visible';

if(document.all) {
    BROWSER=IE;
} else if(document.getElementById) {
    BROWSER=MZ;
} else {
    //document.layers
    BROWSER=NS;
    HID='hide';
    VIS='show';
}  
  

//alert(BROWSER);

function getClientWidth()
{
    if(BROWSER==IE){
        WIDTH=document.body.clientWidth;
    } else if(BROWSER==NS){
        WIDTH=window.innerWidth;
    } else if(BROWSER==MZ){
        WIDTH=window.innerWidth;
    }
    // when WIDTH is odd the div does not line up right
    if(WIDTH%2!=0) WIDTH--;
    return WIDTH;
}

function getClientHeight()
{
    if(BROWSER==IE){
        HEIGHT=document.body.clientHeight;
    } else if(BROWSER==NS){
        HEIGHT=window.innerHeight;
    } else if(BROWSER==MZ){
        HEIGHT=window.innerHeight;
    }
    // when HEIGHT is odd the div does not line up right
    if(HEIGHT%2!=0) HEIGHT--;
    return HEIGHT;
}

function getobj(div)
{
    if(BROWSER==IE){
        obj=document.all[div].style;
    } else if(BROWSER==NS){
        obj=document.layers[div];
    } else if(BROWSER==MZ){
        obj=document.getElementById(div);
        obj=obj.style;
    }
    return obj;
}

function move_div(divname, top, left)
{
    obj=getobj(divname);
    obj.left=newleft;
    obj.top=newtop;
}

function show_dhtml_popup(popup, url)
{

    obj=getobj(popup + 'div');

    obj.visibility=VIS;

    set_dhtml_frame_url(popup, url);

}

function hide_dhtml_popup(popup)
{
    obj=getobj(popup + 'div');
    obj.visibility=HID;
}

function set_dhtml_frame_url(popup, url)
{
    divname = popup + 'div';
    framename = popup + 'frame';

    if(BROWSER==IE){
        frm=eval(framename);
        frm.document.location = url;
    } else if(BROWSER==NS){
        frm=eval('document.'+ divname + '.document.outer' + framename + '.document.' + framename);
        frm.src=url;
    } else if(BROWSER==MZ){
        frm=document.getElementById(framename);
        frm.src = url;
    }
}
