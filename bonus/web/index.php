<html>

<head>

<title>
<?php
$cwd = explode("/",getcwd());
$folder = array_pop($cwd);
echo $folder;
?>
</title>

<!-- <script type="text/javascript" src="http://livejs.com/live.js"></script> -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script defer src="//code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/jstree.min.js"></script>
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/mark.js/8.11.1/jquery.mark.min.js"></script>
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.6.347/pdf.min.js"></script>
<link defer rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/themes/default/style.min.css" />
<link defer rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre.min.css">
<link defer rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-exp.min.css">
<link defer rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-icons.min.css">
<link rel="icon" type="image/png" href="../trashcan.png" />

<style>

mark {
    padding: 0px;
    color: #fff;
    background: #4543dc;
}

#metadatacontainer {
    position: fixed;
    bottom: 0;
    width: 97%;
    padding: 10px; /* space between image and border */
}
#metadata {
    border:0.1rem solid #999;
    border-radius: 8px;
    background-color: rgba(255, 255, 255, .96);
    padding: 10px; /* space between image and border */
}

body {
    font-family: sans-serif;
    background-color: #fff;
}
body.dark-mode {
    /* background-color: #000; */
    background-color: #141414; /* 8% lightness 8*/
}

input[type=text] {
}
input[type=text].dark-mode {
  background-color: #141414;
  color: #dcdcdc;
}

button.dark-mode {
  background-color: #141414;
}

.noborder {
    border: none;
    padding: 0px;
    /* to take up full width without showing horizontal scrollbar */
    width:90vw;
}

fieldset {
    border:0.1rem solid #999;
    border-radius: 8px;
    margin: 1px;
}
fieldset.dark-mode {
    border-color: #dcdcdc;
}

span.label.dark-mode {
    border: 0.1rem solid #dcdcdc;
    margin:-0.1rem;
    color: #dcdcdc;
    background: none;
}

#custom-handle {
    width: 3em;
    font-family: sans-serif;
    text-align: center;
}

#slider {
    float:right;
    width: 10%;
}


.box {
    float:left;
    padding: 3px; /* space between image and border */
}

.plot {
    color: #070;
    text-decoration: none;
    border-bottom: 2px solid #bdb;
}

#images {
    position:relative;
    padding-top: 10px;
}

#container {
    margin: 1%;
}

.innerimg {
    padding: 3px;
}
.innerimg.dark-mode {
    filter: hue-rotate(180deg) invert(0.92); /* 8% lightness */
    -webkit-filter: hue-rotate(180deg) invert(0.92);
}
.innerimg.super-saturate {
    filter: saturate(2.5);
    -webkit-filter: saturate(2.5);
}
.innerimg.dark-mode-super-saturate {
    filter: hue-rotate(180deg) invert(0.92) saturate(2.5);
    -webkit-filter: hue-rotate(180deg) invert(0.92) saturate(2.5);
}


legend {
    font-weight: bold;
    font-size: 90%;
    margin: 0px;
}
legend.dark-mode {
    color: #dcdcdc;
}

a {
}
a.dark-mode {
    color: #55f;
}


</style>

<?php

// get flat list with parent references
$data = array();
function fillArrayWithFileNodes( DirectoryIterator $dir , $theParent="#") {
    global $data;
    foreach ( $dir as $node ) {
        if (strpos($node->getFilename(), '.php') !== false) continue;
        if( $node->isDot() ) continue;
        if ( $node->isDir()) fillArrayWithFileNodes( new DirectoryIterator( $node->getPathname() ), $node->getPathname() );

        $tmp = array(
            "id" => $node->getPathname(),
            "parent" => $theParent,
            "text" => $node->getFilename(),
        );
        if ($node->isFile()) $tmp["icon"] = "file"; // can be path to icon file
        $data[] = $tmp;
    }
}
fillArrayWithFileNodes( new DirectoryIterator( '.' ) );

// get all files in flat list
$iter = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST,
    RecursiveIteratorIterator::CATCH_GET_CHILD
);
$paths = array('.');
foreach ($iter as $path => $dir) $paths[] = $path;

// get number of directories
$num_directories = 0;
foreach ( (new DirectoryIterator('.')) as $node ) {
    if( $node->isDot() ) continue;
    if ( $node->isDir()) $num_directories += 1;
}
?>

<script type="text/javascript">

function contains_any(str, substrings) {
    for (var i = 0; i != substrings.length; i++) {
        var substring = substrings[i];
        if (str.indexOf(substring) != - 1) {
            return substring;
        }
    }
    return null; 
}

function draw_objects(file_objects) {
    $("#images").html("");
    for (var ifo = 0; ifo < file_objects.length; ifo++) {
        var fo = file_objects[ifo];
        var name_noext = fo["name_noext"];
        var name = fo["name"];
        var path = fo["path"];
        var color = fo["color"];
        var pdf = fo["pdf"] || fo["name"];
        if (path) pdf = path+pdf;
        var pdf = escape(pdf);
        var src = escape(path+"/"+name);
        var txt_str = (fo["txt"].length > 0) ? `<span class='label label-rounded label-secondary' style='margin-left: 2px;'><a href='${fo["txt"]}' id='text_${fo["name_noext"]}'>text</a></span>` : "";
        var extra_str = (fo["extra"].length > 0) ? `<span class='label label-rounded' style='margin-left: 2px;'><a href='${fo["extra"]}' id='extra_${fo["name_noext"]}'>extra</a></span>` : "";
        var inner = `<img class='innerimg has-dark' loading='lazy' name='${name_noext}' src='${src}' height='300px' />`;
        if (fo["onlypdf"]) {
            inner = `<canvas class='innerimg has-dark' data-pdf-src='${pdf}' id='canv_${name_noext}' height='300px' ></canvas>`;
        }
        $("#images").append(`<div class='box' id='${name_noext}'>
                    <fieldset class='has-dark'>
                        <legend class='has-dark'>
                            <span class='label label-rounded has-dark'>${name_noext}</span>
                            ${txt_str+extra_str}
                        </legend>
                        <a href='${pdf}'>
                            ${inner}
                        </a>
                    </fieldset>
                </div>`);
    }
}

function draw_filtered(filter_paths) {
    var temp_filelist = filelist.filter(function(value) {
            return contains_any(value, filter_paths);
            });
    var temp_objects = make_objects(temp_filelist);
    draw_objects(temp_objects);
}

function make_objects(filelist) {
    var good_exts = ["png", "svg", "gif", "jpeg", "jpg", "pdf"];
    // basenames (ext stripped) for all files that are not .pdf
    var basenonpdf = filelist.filter((f)=>!f.endsWith(".pdf")).map( (f,_)=>( f.split(".").slice(0, -1).join(".")   ) );
    
    var file_objects = [];
    for (var i = 0; i < filelist.length; i++) {
        var f = filelist[i];
        var ext = f.split('.').pop();
        // skip unknown extensions
        if (!good_exts.includes(ext)) continue;

        // if we have a pdf file that has a corresponding non-pdf file,
        // then we skip the file if it is .pdf (otherwise we double count/render)
        var basename = f.split(".").slice(0,-1).join(".");
        var onlypdf = !basenonpdf.includes(basename);
        if ((ext == "pdf") && !onlypdf) continue;

        var color = "";
        var name = f.split('/').reverse()[0];
        var path = f.replace(name, "");
        var name_noext = name.replace("."+ext,"");
        var pdf = (filelist.indexOf(path+name_noext + ".pdf") != -1) ? name_noext+".pdf" : "";
        var txt = (filelist.indexOf(path+name_noext + ".txt") != -1) ? name_noext+".txt" : "";
        var extra = (filelist.indexOf(path+name_noext + ".extra") != -1) ? name_noext+".extra" : "";
        var json = (filelist.indexOf(path+name_noext + ".json") != -1) ? name_noext+".json" : "";
        file_objects.push({
                "path": path,
                "name_noext": name_noext,
                "name":name,
                "ext": ext,
                "pdf": pdf,
                "txt": txt,
                "extra": extra,
                "json": json,
                "color": color,
                "onlypdf": onlypdf,
                });
    }
    // sort by name
    file_objects.sort(function(a,b) { return a["name"] > b["name"]; });
    return file_objects;
}

function register_hover() {
    $("[id^=text_],[id^=extra_]").hover(
        function() {
            $(this).delay(500).queue(function(){
                $(this).addClass('hovered').siblings().removeClass('hovered');
                var link = $(this).attr('href');
                $("#metadata").load(link, function() { });
                $("#metadata").fadeIn();
            });
        },function() {
            $(this).finish();
            $("#metadata").delay(500).fadeOut();
        } 
    );
}

function register_description_hover() {
    $("[class^=plot]").hover(
        function() {
            var plotname = $(this).text();
            var plotselector = "#" + plotname;
            $(plotselector).effect('highlight',{"color":"9d9"},500);
            $(plotselector).finish();
        },function() {
        } 
    );
}

function add_links_to_description(objects) {
    var desc_src = $("#description").html();
    for (var i = 0; i < objects.length; i++) {
        var plotname = objects[i]["name_noext"];
        desc_src = desc_src.split(plotname).join("<a href=\"#"+plotname+"\" class=\"plot\">"+plotname+"</a>");
    }
    $("#description").html(desc_src);
}

function enable_pdfjs() {
    var elements = document.querySelectorAll("canvas[data-pdf-src]");
    if (elements.length < 1) return;

    var pdfjsLib = window['pdfjs-dist/build/pdf'];
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.6.347/pdf.worker.min.js';

    elements.forEach(function(element,idx) {
        var canvasid = element.id;
        var pdfurl = element.getAttribute("data-pdf-src");

        var loadingTask = pdfjsLib.getDocument(pdfurl);
        loadingTask.promise.then(function(pdf) {

            pdf.getPage(1).then(function(page) {

                var canvas = document.getElementById(canvasid);
                var height = canvas.height;
                var context = canvas.getContext("2d");
                var viewport = page.getViewport({scale: 5});
                canvas.width = viewport.width;
                canvas.height = viewport.height;
                canvas.style.height = height;

                var renderContext = {
                    canvasContext: context,
                    viewport: viewport,
                };
                var renderTask = page.render(renderContext);
                renderTask.promise.then(function () {
                });
            });
        }, function (reason) {
            // PDF loading error
            console.error(reason);
        });

    });
}
// ultimately this will be a master filelist with all files recursively in this directory
// then we will filter for files we want to show
var obj = <?php echo json_encode($data); ?>;
var filelist = <?php echo json_encode($paths); ?>;


$(function() {

    if (<?php echo $num_directories ?> > 0) {
        $('#jstree_demo_div')
            .on('changed.jstree', function(e,data) {
                draw_filtered(data.selected);
            })
            .jstree( {
                "core": {
                    'multiple': true,
                    'themes' : {
                       'stripes' : true
                    },
                    "data": 
                        obj
                }
            }); 
    }

    var markre = function(pattern) {
        
        var context=$("legend");
        $("#message").html("");
        $("#message").removeClass("label");
        context.unmark();

        var modifier = "";
        if (pattern.toLowerCase() == pattern) modifier = "i"; // like :set smartcase in vim (case-sensitive if there's an uppercase char)

        $(".form-icon").addClass("loading");

        document.location.hash = escape($('#filter').val());
        var regex = new RegExp(pattern,modifier);
        context.markRegExp(regex,{
            done: function(counter) {
                $(".form-icon").removeClass("loading");
                if (counter > 0) {
                    // show all matches and hide those that don't match
                    context.not(":has(mark)").parent().parent().hide();
                    var toshow = context.has("mark").parent().parent();
                    var nmatches = toshow.length;
                    toshow.show();
                    register_hover();
                    $("#filterbadge").addClass("badge");
                    $("#filterbadge").attr("data-badge",nmatches);
                } else {
                    context.parent().parent().show();
                    if (pattern.length > 0) {
                        $("#filterbadge").addClass("badge");
                        $("#filterbadge").attr("data-badge",0);
                    } else {
                        $("#filterbadge").removeClass("badge");
                    }
                }
            },
        });
    };

    var timer;
    var lastPattern = "";
    var timeoutms = 300;
    if (obj.length < 400) {
        timeoutms = 0;
    }
    $("input[id='filter']").keyup(function(e) {
        var pattern = $(this).val();
        if (pattern == lastPattern) return;
        if (lastPattern == "") {
            lastPattern = this.value;
            return;
        } else {
            lastPattern = this.value;
        }
        clearTimeout(timer);
        timer = setTimeout(function() {
            markre(lastPattern);
        }, timeoutms);
    });

    var handle = $( "#custom-handle" );
    // $("#slider").change(function() {
    $("#slider").bind("input",function() {
        var val = $(this).val();
        $("img").attr("height",300*val/100);
        $("canvas").attr("style",`height: ${300*val/100}px`);
        if ((val == 0 && imagesVisible) || (val != 0 && !imagesVisible)) {
            toggleImages();
        }
    });


        var file_objects = make_objects(filelist);
        draw_objects(file_objects);

    // if page was loaded with a parameter for search, then simulate a search
    // ex: https://foo.bar/plots_isfr_Aug26/#HH$
     if(document.location.hash.length > 0) {
        var search = unescape(document.location.hash.substring(1));
        $("#filter").val(search);
        markre($("#filter").val());
    }

    register_hover();
    add_links_to_description(file_objects);
    // register hover for links in description AFTER adding them
    register_description_hover();
    enable_pdfjs();

    if (sessionStorage.getItem("darkMode") == 1) {
        toggleDarkMode();
    }

});

// vimlike incsearch: press / to focus on search box
$(document).keydown(function(e) {
    var modifierNoShift = e.metaKey || e.ctrlKey || e.altKey;
    var modifier = e.shiftKey || modifierNoShift;
    if(e.keyCode == 191 && !modifier) {
        // / focus search box
        e.preventDefault();
        $("#filter").focus().select();
    }
    if (!$(event.target).is(":input")) {
        if(e.keyCode== 89 && !modifier) {
            // y
            getQueryURL();
        }
        if(e.keyCode == 71 && !modifierNoShift) {
            // G scrolls to bottom, g to top
            if (e.shiftKey) {
                window.scrollTo(0,document.body.scrollHeight);
            } else {
                window.scrollTo(0,0);
            }
        }
        if(e.keyCode == 83 && !modifierNoShift) {
            // s and shift S to sort a-z or z-a
            if (e.shiftKey) {
                $("#images").html($(".box").sort(function (a,b) { return $(a).attr("id").localeCompare($(b).attr("id")); }));
            } else {
                $("#images").html($(".box").sort(function (a,b) { return -$(a).attr("id").localeCompare($(b).attr("id")); }));
            }
        }
        if(e.keyCode == 77 && !modifier) {
            // m to toggle dark mode
            toggleDarkMode();
        }
        if(e.keyCode == 66 && !modifier) {
            // b to toggle super saturation mode
            toggleSaturation();
        }
        if(e.keyCode == 88 && !modifier) {
            // x to show and hide images
            toggleImages();
        }
    }
});

function copyToClipboard(text) {
    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val(text).select();
    document.execCommand("copy");
    $temp.remove();
    $("#message").html("Copied to clipboard!").delay(600).queue(function(n) {$(this).html("");n();});
}

function getQueryURL() {
    copyToClipboard(location.href);
}

var darkMode = false;
function toggleDarkMode() {
    $(".has-dark").toggleClass("dark-mode");
    // $(".legendname").toggleClass("label-secondary");
    if (superSaturation) {
        toggleSaturation();
    }
    darkMode ^= true;
    if (darkMode) {
        sessionStorage.setItem("darkMode", 1);
    } else {
        sessionStorage.setItem("darkMode", 0);
    }
}

var superSaturation = false;
function toggleSaturation() {
    if (darkMode) {
        $(".innerimg").toggleClass("dark-mode-super-saturate");
    } else {
        $(".innerimg").toggleClass("super-saturate");
    }
    superSaturation ^= true;
}
var imagesVisible = true;
function toggleImages() {
    $("img").toggle();
    $("canvas").toggle();
    $("fieldset").toggleClass("noborder");
    imagesVisible ^= true;
}

</script>

</head>

<body class="has-dark">

    <div id="container">

        <div id="jstree_demo_div"> </div>

        <div class="has-icon-right" style="width: 200px; display: inline-block;">
            <input type="text" class="form-input input-sm inputbar has-dark" id="filter" placeholder="Search/filter" />
            <span class="form-icon" id="filterbadge" data-badge=""></span>
        </div>

        &nbsp;


        <div class="popover popover-bottom">
            <button class="btn btn btn-sm has-dark">help</button>
            <div class="popover-container">
                <div class="card">
                    <div class="card-header">
                        Keybindings
                    </div>
                    <div class="card-body">
                        <kbd>g</kbd> / <kbd>G</kbd> to scroll to top/bottom <br>
                        <kbd>/</kbd> to focus the search box <br>
                        <kbd>y</kbd> to copy the filter state as a URL <br>
                        <kbd>s</kbd> / <kbd>S</kbd> to sort A-Z/Z-A <br>
                        <kbd>b</kbd> to toggle super-saturation mode <br>
                        <kbd>m</kbd> to toggle dark mode <br>
                        <kbd>x</kbd> to toggle image visibility <br>
                    </div>
                    <div class="card-footer">
                        <a href="https://github.com/aminnj/niceplots">View my source on GitHub</a>
                    </div>
                </div>
            </div>
        </div>

        &nbsp;
        <input id="slider" class="slider input-sm tooltip tooltip-bottom" type="range" min="0" max="300" value="100" oninput="this.setAttribute('value', this.value);">
        <!-- <span id="message"></span> -->
<div id="description">
<?php
$description = @file_get_contents("description.txt");
if( $description ) {
    echo "<br><b>Description:</b><br>";
    echo $description;
}
?>
</div>
<div id="images"></div>
<div id="metadatacontainer"  style="text-align: center;">
<div id="metadata" style="display: inline-block; text-align: left; display: none">
</div>
</div>

<canvas id='hovercanvas' width="50" height="300"></canvas>
</div>


</body>
</html>

