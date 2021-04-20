'use strict';
var params = (new URL(document.location)).searchParams
var pagecount = params.get("pagecount");

var parentid = JSON.parse($("#parentid").text());




pagecount = pagecount - 1;

  const func1 = () => {
      var countdown = parentid;

      var size = document.getElementsByClassName("toggle-wrap").length;

      var elements = document.getElementsByName("toggle");

      console.log(elements);

      for (let i = 0; i <= elements.length; i++){        
        if (elements[i].checked){

          var j = countdown;
          if(pagecount >= 1){j = countdown - 5 * pagecount};
          console.log(j);
          const content = document.getElementById(`content_${j}`);

          content.classList.toggle("active");
          content.classList.toggle("hidden");
        }
        countdown--;
      }

  }


