<!DOCTYPE html>
<html>

  <head>
 
    <script>
      function iterateNodes(nodes) {
        for (var i = 0; i < nodes.length; i++) {
          if (nodes[i].nodeType ) {
            document.body.innerHTML += nodes[i].textContent + "<br>"
            console.log(nodes[i])
          };
            if (nodes[i].childNodes.length) {
              iterateNodes(nodes[i].childNodes)
              //console.log(nodes[i].childNodes)
            }
          }
      }
      window.addEventListener("load", function() {
        
        var request = new XMLHttpRequest();
        request.addEventListener("load", function() {
          var parser = new DOMParser(); 
          var xml = parser.parseFromString( this.response, "text/xml" )
          var nodes = xml.documentElement.childNodes;
          iterateNodes(nodes)
          
        });
        request.open("GET", "http://eluniversal-itfr01a.calipso.com.co/news-portlet/getArticle/6545462");
        request.send();
      })
    </script>
  </head>

  <body>
    
  </body>

</html>