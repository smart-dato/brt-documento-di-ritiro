<html>
<head>
  <style>
    html {
      margin: 0px;
      padding: 0px;
    }

    body {
      margin: 0px;
      padding: 20px 0px 0px 0px;
    }
  </style>
</head>
<body>
@foreach($labelData AS $label)
  <center>
    <img src="{{ $label }}" width="790" height="1000"/>
  </center>
@endforeach
</body>
</html>
