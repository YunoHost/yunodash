<!DOCTYPE html>
<html lang="en">
  <head>
  <title>YunoHost • Application status</title>
  
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
      <meta name="format-detection" content="telephone=no" />
      <meta name="viewport" content="user-scalable=no, width=device-width, height=device-height" />
      <link rel="shortcut icon" href="/favicon.ico">
      
      <link rel="stylesheet" type="text/css" href="css/bootstrap-3.1.1.min.css">
      <link rel="stylesheet" type="text/css" href="css/bootstrap-3.1.1-theme.min.css">
      <link rel="stylesheet" type="text/css" href="css/custom.css">
      
      <!-- Always define js console -->
      <script type="text/javascript">
        if (typeof console === "undefined" || typeof console.log === "undefined")
        {
          console = {};
          console.log = function () {};
        }
      </script>
      
      <script type="text/javascript" src="js/jquery-1.11.1.min.js"></script>
      <script type="text/javascript" src="js/bootstrap-3.1.1.min.js"></script>
      
      {% block scripts %}
      {% endblock %}
  </head>

  <body>
    
      {% block content %}
      {% endblock %}

  </body>
</html>
