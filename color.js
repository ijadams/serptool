var bg = "#";

var options = ['a','b','c','d','e','f','1','2','3','4','5','6','7','8','9','0']

for (var i = 0 ; i < 6 ; i++) {
      bg += options[Math.floor(Math.random()*16)];        
}

   document.getElementsByTagName('body')[0].style.background = bg;
       


