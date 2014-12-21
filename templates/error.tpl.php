<?php
/*
 * NOTE: THIS IS NOT A SAVANT TEMPLATE.
 * 
 * THIS IS DIRECTLY INCLUDED.
 */?>
<!DOCTYPE HTML>
<html>
<head>
    <title>VGWS :: Error</title>
    
    <link rel="shortcut icon" href="/favicon.ico" />
    
    <style type="text/css">
    body {
        width: 100% !important;
    }
    section.errorbox {
        border:1px solid #ccc;
        background:#efefef;
        width:75%;
        margin:auto;
        border-radius:6px;
    }
    
    section.errorbox h1 {
        font-size:large;
        padding:3px;
        display:block;
        background:#efefef linear-gradient(to bottom,#efefef 0%, #999 100%);
        margin:0;
        border-bottom:1px solid #333;
        color:#666;
    }
    
    section.errorbox section.content {
        padding:1em;
        background:white;
    }
    
    section.errorbox section.footer {
        padding:3px;
        background:#efefef;
        color:#ccc;
        text-align:center;
    }
    </style>
</head>
<body>
    <section class="errorbox">
        <h1>Error</h1>
        <section class="content">
            <p>
                <?=$msg?>
            </p>
        </section>
    </section>
</body>
</html>
