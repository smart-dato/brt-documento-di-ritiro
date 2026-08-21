<html>
<head>
    <style>
        html {
            height: 100%;
            width: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            height: 100%;
            width: 100%;
            margin: 0;
            padding: 0;
        }

        img {
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
@if($labelData1 != null)
    <center>
        <img src="{{ $labelData1 }}" width="790" height="1000"/>
        @if(isset($barcode))
            <img src="{{ $barcode }}" width="250" height="100"/>
        @endif
    </center>
@endif

@if($labelData2 != null)
    <center>
        <img src="{{ $labelData2 }}" width="790" height="1000"/>
        @if(isset($barcode))
            <img src="{{ $barcode }}" width="250" height="100"/>
        @endif
    </center>
@endif
</body>
</html>
