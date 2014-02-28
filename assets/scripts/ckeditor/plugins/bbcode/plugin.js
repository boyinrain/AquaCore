﻿(function(){function m(a){return a.replace(/\(|\)|\[|\]|\{|\}|\:|\/|\?|\+|\$|\.|\^|\*|\-|\|/g,function(a){return"\\"+a})}function n(a){return a.replace(/&amp;/g,"&").replace(/&lt;/g,"<").replace(/&gt;/g,">").replace(/&quot;/g,'"').replace(/&#39;/g,"'").replace(/&nbsp;/g," ").replace(/&shy;/g,"­")}function o(a){return a.replace(/ *, */g,",").replace(/"|'/g,"").toLowerCase()}CKEDITOR.config.bbCodeAutoClose=!0;CKEDITOR.config.bbCodeTags="all";CKEDITOR.config.bbCodeFilters="all";CKEDITOR.plugins.bbCode=
{};CKEDITOR.plugins.bbCode.COLOR_PATTERN=/^(\#[a-f0-9]{3,6}|[a-z]+)$/i;CKEDITOR.plugins.bbCode.NUMBER_PATTERN=/^([1-9]+[0-9]*|[0-9])$/i;CKEDITOR.plugins.bbCode.bbCodeParser={};CKEDITOR.plugins.bbCode.bbCodeParser.parse=function(a){var b,c,d,f=0,g=/(?:\[([^\/\]"'= ]+)(?:=("[^"]*"|'[^']*'|[^\]"' ]*)?|((?: [^\/\]="' ]+=(?:"[^"]*"|'[^']*'|[^\]"' ]*))*))?\]|\[\/([^\/\]"'= ]+)\])/g;for(b=c=new CKEDITOR.plugins.bbCode.BBCodeNode(CKEDITOR.NODE_DOCUMENT,{bbCode:a});d=g.exec(a);){f<d.index&&c.append(new CKEDITOR.plugins.bbCode.BBCodeNode(CKEDITOR.NODE_TEXT,
{value:a.substring(f,d.index)}));if(d[2]){var f=d[2].charAt(0),e=d[2].charAt(d[2].length-1);if(f===e&&("'"===f||'"'===e))d[2]=d[2].substring(1,d[2].length-1)}d[4]?c=c.close(d[4].toLowerCase(),d[0]):(d=new CKEDITOR.plugins.bbCode.BBCodeNode(CKEDITOR.NODE_ELEMENT,{openTag:d[0],name:d[1],value:d[2]||"",attributes:d[3]?this.parseAttributes(d[3]):{}}),c.append(d),c=d);f=g.lastIndex}a.length>f&&b.append(new CKEDITOR.plugins.bbCode.BBCodeNode(CKEDITOR.NODE_TEXT,{value:a.substring(f,a.length)}));return b};
CKEDITOR.plugins.bbCode.bbCodeParser.parseAttributes=function(a){for(var b,c={},d=/(?:([^= ]+)+=(?:('[^']*'|"[^"]*")|([^ ]*)))/g;b=d.exec(a);)c[b[1]]=b[2]?b[2].substring(1,b[2].length-1):b[3];return c};CKEDITOR.plugins.bbCode.BBCodeNode=function(a,b){this.type=a;this.children=[];this.isEmpty=!0;this.nodeLevel=0;CKEDITOR.tools.extend(this,b,!1);this.closeTag=this.closeTag||"";this.openTag=this.openTag||"";if((this.value=this.value||"")&&this.attributes)this.attributes.$=this.value};CKEDITOR.plugins.bbCode.BBCodeNode.prototype=
{forEach:function(a){var b,c,d=Array.prototype.splice.call(arguments,1,arguments.length),f=this.children.length;for(b=0;b<f;++b)if(this.children[b].type!==CKEDITOR.NODE_DELETED&&(c=a.apply(this.children[b],d),!1===c))return!1;return c},append:function(a){return this.add(a,0)},prepend:function(a){return this.add(a)},add:function(a,b){var c;c=this.children.length;a.parent=this;a.nodeLevel=this.nodeLevel+1;"undefined"===typeof b||c>b?(c&&(c=this.children[c-1],a.previous=c,c.next=a),this.children.push(a)):
0>=b?(c&&(c=this.children[0],a.next=c,c.previous=a),this.children.unshift(a)):(c=this.children[b],c.previous&&(a.previous=c.previous,c.previous.next=a),a.next=c,c.previous=a,this.children.splice(b,0,a));this.isEmpty=!1;return this},clear:function(){this.children=[];this.isEmpty=!0;return this},close:function(a,b){if(this.type===CKEDITOR.NODE_ELEMENT){if(a===this.name)return this.closeTag=b,this.parent;if(this.parent){var c=this.parent;do if(c.name===a)return c.close(a,b);while(c=c.parent)}}this.append(new CKEDITOR.plugins.bbCode.BBCodeNode(CKEDITOR.NODE_TEXT,
{value:b}));return this},content:function(){if(this.type===CKEDITOR.NODE_TEXT)return this.value;var a="";this.forEach(function(){a+=this.openTag+this.content()+this.closeTag});return a}};CKEDITOR.plugins.bbCode.BBCode=function(a){var b,c=this,d=a.config,f=[],g=[];this.editor=a;f="all"===d.bbCodeTags?"b s u i sub sup url email img color background size font indent center right left justify youtube vimeo spoiler hide acronym quote list".split(" "):d.bbCodeTags.split(/ *, */g);g="all"===d.bbCodeFilters?
["smiley"]:d.bbCodeFilters.split(/ *, */g);for(b=0;b<f.length;++b)switch(f[b]){case "b":this.defineTag("b","strong",{allowedContent:"strong b",alternativeTags:["b"]});break;case "s":this.defineTag("s","s",{allowedContent:"s strike",alternativeTags:["strike"]});break;case "i":this.defineTag("i","em",{allowedContent:"i em",alternativeTags:["i"]});break;case "u":this.defineTag("u","u",{allowedContent:"u"});break;case "sub":this.defineTag("sub","sub",{allowedContent:"sub"});break;case "sup":this.defineTag("sup",
"sup",{allowedContent:"sup"});break;case "url":var e=this.defineTag("url","a",{allowedContent:"a[!href]",attributes:{href:{map:"$",required:!0,encode:encodeURI,decode:decodeURI,htmlPattern:/^(?!mailref:)/i}}});e.on("beforeToHtml",function(a){a=a.data.node;a.value||(a.attributes.$=CKEDITOR.tools.trim(a.content()))});e.on("toBBCode",function(a){var b=a.data.node;!b.isEmpty&&(b.children.length&&b.children[0].type===CKEDITOR.NODE_TEXT&&n(b.children[0].value)===n(a.data.bbCode.value))&&(a.data.bbCode.value=
void 0)});break;case "email":this.defineTag("email","a",{attributes:{allowedContent:"a[!href]",href:{map:"$",required:!0,encode:encodeURIComponent,decode:decodeURIComponent,bbCodeFormat:"mailref:{1}",htmlPattern:/^mailref:(.*)$/i,htmlFormat:"{1}"}}});break;case "img":e=this.defineTag("img","img",{allowedContent:"img[!src,alt,title]",attributes:{src:{map:"$",required:!0,encode:encodeURI,decode:decodeURI},alt:{map:"alt",required:!1},title:{map:"alt",required:!1}}});e.on("beforeToHtml",function(a){a=
a.data.node;a.attributes.$?a.attributes.alt=a.content():a.attributes.$=CKEDITOR.tools.trim(a.content());a.clear()});e.on("toBBCode",function(a){var b=a.data.node,a=a.data.bbCode;a.attributes.alt?(b.add(new CKEDITOR.htmlParser.text(a.attributes.alt),0),b.isEmpty=!1,delete a.attributes.alt):(b.add(new CKEDITOR.htmlParser.text(a.value),0),b.isEmpty=!1,a.value=void 0)});break;case "color":this.defineTag("color","span",{allowedContent:"span{!color}",attributes:{style:{map:"$",style:"color",required:!0,
bbCodePattern:CKEDITOR.plugins.bbCode.COLOR_PATTERN,htmlPattern:CKEDITOR.plugins.bbCode.COLOR_PATTERN}}});break;case "background":this.defineTag("background","span",{allowedContent:"span{!background-color}",attributes:{style:{map:"$",style:"background-color",required:!0,bbCodePattern:CKEDITOR.plugins.bbCode.COLOR_PATTERN,htmlPattern:CKEDITOR.plugins.bbCode.COLOR_PATTERN}}});break;case "size":for(var e={},h=[],i=/(?:([^;\/]+)\/)?([^;\/]+);?/g,j,k=1;j=i.exec(a.config.fontSize_sizes);)j[1]?(e[j[1].toLowerCase()]=
j[2],h.push(j[1])):(e[k]=j[2],h.push(k)),++k;this.defineTag("size","span",{allowedContent:"span{!font-size}",sizes:e,attributes:{style:{map:"$",style:"font-size",required:!0,bbCodePattern:RegExp("^("+h.join("|")+")$","i"),bbCodeFormat:function(a){return this.options.sizes[a.toLowerCase()]},htmlFormat:function(a){for(var b in this.options.sizes)if(this.options.sizes.hasOwnProperty(b)&&this.options.sizes[b]===a)return b}}}});break;case "font":var l,h={},i={};j=d.font_names.split(";");for(k=0;k<j.length;++k)j[k]&&
((l=/^([^\/]+)\/(.+)$/.exec(j[k]))?(e=l[1],l=l[2]):e=l=j[k],h[e.toLowerCase()]=CKEDITOR.tools.htmlEncodeAttr(l),i[o(l)]=e);this.defineTag("font","span",{allowedContent:"span{!font-family}",fontFamilyMap:h,fontFamilyReverseMap:i,attributes:{style:{style:"font-family",required:!0,bbCodeFormat:function(a){a=a.toLowerCase();if(this.options.fontFamilyMap.hasOwnProperty(a))return this.options.fontFamilyMap[a]},htmlFormat:function(a){a=o(a);if(this.options.fontFamilyReverseMap.hasOwnProperty(a))return this.fontFamilyReverseMap[a]}}}});
break;case "indent":this.defineTag("indent","div",{allowedContent:"div{margin-left,margin-right};p{margin-left,margin-right}",priority:0,alternativeTags:["ul","ol","p"],max:0,step:a.config.indentOffset||40,unit:a.config.indentUnit||"px",attributes:{style:{style:"margin-left",required:!0,bbCodePattern:CKEDITOR.plugins.bbCode.NUMBER_PATTERN,bbCodeFormat:function(a){a=Number(a);return!a||0<this.options.max&&a>this.options.max?!1:""+a*this.options.step+this.options.unit},htmlFormat:function(a){var b=
this.options.unit;if(a.indexOf(b)!==a.length-b.length)return!1;a=a.substring(0,a.length-b.length);if(isNaN(a)||!isFinite(a))return!1;a=Number(a)/this.options.step;return a%1||this.options.max&&a>this.options.max?!1:""+a}}}});break;case "center":this.defineTag("center","div",{allowedContent:"div{text-align}",priority:1,alternativeTags:["ul","ol","pre","p"],attributes:{style:{required:!0,style:"text-align",value:"center"}}});break;case "left":this.defineTag("left","div",{allowedContent:"div{text-align}",
priority:1,alternativeTags:["ul","ol","pre","p"],attributes:{style:{required:!0,style:"text-align",value:"left"}}});break;case "right":this.defineTag("right","div",{allowedContent:"div{text-align}",priority:1,alternativeTags:["ul","ol","pre","p"],attributes:{style:{required:!0,style:"text-align",value:"right"}}});break;case "justify":this.defineTag("justify","div",{allowedContent:"div{text-align}",priority:1,alternativeTags:["ul","ol","pre","p"],attributes:{style:{required:!0,style:"text-align",value:"justify"}}});
break;case "youtube":e=this.defineTag("youtube","iframe",{allowedContent:"iframe[src,width,height,frameborder,contenteditable,youtube-player-start]",priority:10,attributes:{src:{map:"src",encode:encodeURI,decode:decodeURI,required:!0,bbCodePattern:/(?:youtube(?:-nocookie)?\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i,bbCodeFormat:"https://youtube.com/embed/{1}",htmlPattern:/^https?:\/\/youtube.com\/embed\/([^"&?\/ ]{11})/i,htmlFormat:"https://www.youtube.com/watch?v={1}"},
"youtube-player-start":{map:"$",required:!1,bbCodePattern:/(^[0-9]+$)|(^(?:\d+:)?(?:\d\d?[:.])?\d\d?$)/,bbCodeFormat:function(a){if(!a.match(/^[0-9]+$/)){var b=a.split(/[:.]/g);b.reverse();a=0;if(3<b.length||0===b.length)return!1;for(var c=0;c<b.length;++c){if(isNaN(b[c])||2>c&&59<b[c])return;a+=Number(b[c])*Math.pow(60,c)}a=""+a}return a},htmlPattern:/\d+/},width:{value:"640"},height:{value:"390"},frameborder:{value:"0"},contenteditable:{value:"false"}}});e.on("beforeToHtml",function(a){a=a.data.node;
a.attributes.src=a.content();a.clear()});e.on("beforeToBBCode",function(a){var a=a.data.node,b=a.attributes.src,c;if(b&&!a.attributes["youtube-player-start"]&&(c=b.match(/\?start=([0-9]+)/i)))a.attributes["youtube-player-start"]=c[1]});e.on("toHtml",function(a){a=a.data.html;a.attributes["youtube-player-start"]&&(a.attributes.src+="?start="+a.attributes["youtube-player-start"])});e.on("toBBCode",function(a){var b=a.data.node,a=a.data.bbCode;b.add(new CKEDITOR.htmlParser.text(a.attributes.src),0);
b.isEmpty=!1;delete a.attributes.src});break;case "vimeo":e=this.defineTag("vimeo","iframe",{allowedContent:"iframe[src,width,height,frameborder,contenteditable]",priority:10,attributes:{src:{map:"src",required:!0,bbCodePattern:/^(?:https?:\/\/)?(?:www\.)?(?:player\.)?vimeo\.com\/(?:[a-z]*\/)*([0-9]{6,})[?]?./i,bbCodeFormat:"//player.vimeo.com/video/{1}",htmlPattern:/^(?:https?:)?\/\/player.vimeo.com\/video\/([0-9){6,}])/i,htmlFormat:"http://www.vimeo.com/{1}"},width:{value:"640"},height:{value:"390"},
frameborder:{value:"0"},contenteditable:{value:"false"}}});e.on("beforeToHtml",function(a){a=a.data.node;a.attributes.src=a.content();a.clear()});e.on("toBBCode",function(a){var b=a.data.node,a=a.data.bbCode;b.add(new CKEDITOR.htmlParser.text(a.attributes.src),0);b.isEmpty=!1;delete a.attributes.src});break;case "hide":this.defineTag("hide","span",{allowedContent:"span(bbc-hide-text)",attributes:{"class":{required:!0,value:"bbc-hide-text"}}});break;case "spoiler":this.defineTag("spoiler","div",{allowedContent:"div[!cke-spoiler-wrapper](!spoiler-wrapper);div(!spoiler-title);div(!spoiler-content);",
renderOpenTagHtml:function(){return'<div class="spoiler-wrapper" cke-spoiler-wrapper="1"><div class="spoiler-title" >'+(this.editor.lang.hasOwnProperty("spoiler")?this.editor.lang.spoiler.title:"Spoiler")+'</div><div class="spoiler-content">'},renderCloseTagHtml:function(){return"</div></div>"},parseContent:!1,attributes:{"class":{required:!0,value:"bbc-spoiler"}}}).on("toBBCode",function(a){var a=a.data.node,b,c;if(a.isEmpty)return!1;for(var d=0;d<a.children.length;++d)a.children[d].hasClass("spoiler-title")?
b=d:a.children[d].hasClass("spoiler-content")&&(c=d);if("undefined"===typeof b||"undefined"===typeof c)return!1;b=a.children[c];b.isEmpty?(a.children=[],a.isEmpty=!0):(a.children=b.children,a.isEmpty=!1)});break;case "acronym":this.defineTag("acronym","acronym",{allowedContent:"acronym[!title]",attributes:{title:{map:"$",required:!0}}});break;case "quote":this.defineTag("quote","blockquote",{allowedContent:"blockquote;div[!contenteditable](!quote-header)",renderOpenTagHtml:function(a,b){var c;if(b.attributes.username||
b.attributes.date)return c='<blockquote><div class="quote-header">',b.attributes.username&&(c+='<span class="quote-user">'+b.attributes.username+"</span>"),b.attributes.date&&(c+='<span class="quote-date">'+b.attributes.date+"</span>"),c+'</div><div class="quote-content">'},renderCloseTagHtml:function(a,b){return b.attributes.username||b.attributes.date?"</div></blockquote>":"</blockquote>"},postUrlFormat:null,attributes:{username:{map:"name",required:!1,encode:CKEDITOR.tools.htmlEncode,decode:n},
date:{map:"date",required:!1,encode:!1,decode:!1,bbCodeFormat:function(a){return Date.parse(a)||void 0},htmlPattern:CKEDITOR.plugins.bbCode.NUMBER_PATTERN,htmlFormat:function(a){return Date(a).toLocaleDateString()||void 0}}}}).on("beforeToBBCode",function(){})}for(b=0;b<g.length;++b)switch(g[b]){case "smiley":this.addFilter("smiley",new CKEDITOR.plugins.bbCode.SmileyFilter(a))}a.on("instanceReady",function(){for(var b in c.tags)if(c.tags.hasOwnProperty(b)){var d=c.tags[b].options.allowedContent;"string"===
typeof d&&!a.filter.check(d,!0,!0)&&a.filter.allow(d,"bbCode",!0)}})};CKEDITOR.plugins.bbCode.BBCode.prototype={tags:{},filters:{},skipBr:0,output:[],editor:null,defineTag:function(a,b,c){b=new CKEDITOR.plugins.bbCode.BBCodeRule(this.editor,a,b,c);return this.tags[a]=b},addFilter:function(a,b){this.filters[a]=b;return this},toHtml:function(a){this.writeBBCodeNode(CKEDITOR.plugins.bbCode.bbCodeParser.parse(a),!0);a=this.output.join("");this.reset();return a},toBBCode:function(a){this.writeHtmlNode(CKEDITOR.htmlParser.fragment.fromHtml(a),
!0);a=this.output.join("");this.reset();return a},writeHtmlNode:function(a,b){var c,d="",f="";c={node:a,parse:b};if(!1!==this.applyFilters(c,"bbCode","beforeParse"))if(a=c.node,b=c.parse,a.type===CKEDITOR.NODE_TEXT)d=n(a.value||""),c={node:a,parse:b,content:d},!1!==this.applyFilters(c,"bbCode","afterParse")&&this.output.push(c.content);else{if(a.type===CKEDITOR.NODE_ELEMENT){var g;if(b){if("br"===a.name){this.output.push("\r\n");return}c=[];var e;a.attributes.style=CKEDITOR.tools.parseCssText(a.attributes.style||
"");for(var h in this.tags)if(this.tags.hasOwnProperty(h)){var i=this.tags[h];a.name.match(i.htmlRegExp)&&(i.checkValidHtmlContext(a)&&(e=i.toBBCode(a)))&&c.push(e)}if(c.length){d=[];f=[];c.sort(this.sortPriority);for(e=0;e<c.length;++e){i=h="";c[e].brBeforeOpen&&(i+="\r\n");if(c[e].renderOpenTag)i+=c[e].renderOpenTag(a,c[e],e);else{i+="["+c[e].name;if(c[e].value)i+='="'+c[e].value+'"';else if(c[e].attributes.length)for(g in c[e].attributes)c[e].attributes.hasOwnProperty(g)&&(i+=" "+g+'="'+c[e].attributes[g]+
'"');i+="]"}c[e].brAfterOpen&&(i+="\r\n");c[e].brBeforeClose&&(h+="\r\n");h=c[e].renderCloseTag?h+c[e].renderCloseTag(a,c[e],e):h+("[/"+c[e].name+"]");c[e].brAfterClose&&(h+="\r\n");f.unshift(h);d.push(i)}b=Boolean(c[0].parseContent);d=d.join("");f=f.join("")}}else{d="<"+a.name;if(a.attributes.length)for(g in a.attributes)a.attributes.hasOwnProperty(g)&&(d+=" "+g+'="'+a.attributes[g]);d+=">";if(!a.isOptionalClose||!a.isEmpty)f="</"+a.name+">"}}c={node:a,parse:b,open:d,close:f};if(!1!==this.applyFilters(c,
"bbCode","afterParse")){c.open&&this.output.push(c.open);if(!a.isEmpty&&a.children&&a.children.length)for(d=0;d<a.children.length;++d)this.writeHtmlNode(a.children[d],b);c.close&&this.output.push(c.close)}}},writeBBCodeNode:function(a,b){var c,d=!1,f=a.openTag,g="",g=a.closeTag;c={node:a,parse:b};if(!1!==this.applyFilters(c,"html","beforeParse"))if(a=c.node,b=c.parse,a.type===CKEDITOR.NODE_TEXT)g=CKEDITOR.tools.htmlEncode(a.value),b&&(g=g.replace(/\r\n|[\r\n]/g,"<br>")),this.skipBr&&(g=g.replace(/(^<br>)/,
""),this.skipBr=!1),c.content=g,!1!==this.applyFilters(c,"html","afterParse")&&this.output.push(c.content);else{if(a.type===CKEDITOR.NODE_ELEMENT){var e,h;if(b&&(g||this.editor.config.bbCodeAutoClose)&&this.tags.hasOwnProperty(a.name)&&this.tags[a.name].checkValidBBCodeContext(a)&&(h=this.tags[a.name].toHtml(a))){d=a.isEmpty?0:a.children.length;if(h.renderOpenTag)f=h.renderOpenTag(a,h);else{f="<"+h.name;if("object"===typeof h.attributes.style){for(var i in h.attributes.style){h.attributes.style.hasOwnProperty(i)&&
(h.attributes.style=CKEDITOR.tools.writeCssText(h.attributes.style));break}"string"===typeof h.attributes.style||delete h.attributes.style}for(e in h.attributes)h.attributes.hasOwnProperty(e)&&(f+=" "+e+'="'+h.attributes[e]+'"');f+=">"}g=h.renderCloseTag?h.renderCloseTag(a,h):"</"+h.name+">";h.brBeforeOpen&&this.output[this.output.length-1].replace(/(<br>$)/,"");h.brAfterClose&&(this.skipBr=!0);h.brBeforeClose&&(d&&a.children[d-1].type===CKEDITOR.NODE_TEXT)&&(a.children[d-1].value=a.children[d-1].value.replace(/(\r\n|[\r\n])$/,
""));h.brAfterOpen&&(d&&a.children[0].type===CKEDITOR.NODE_TEXT)&&(a.children[0].value=a.children[0].value.replace(/^(\r\n|[\r\n])/,""));d=h.stripContent;b=Boolean(h.parseContent)}}c.close=g;c.open=f;if(!1!==this.applyFilters(c,"html","afterParse")){c.open&&this.output.push(c.open);if(b){if(!a.isEmpty&&!0!==d)for(f=0;f<a.children.length;++f)d!==a.type&&this.writeBBCodeNode(a.children[f],b)}else this.output.push(CKEDITOR.tools.htmlEncode(a.content()));c.close&&this.output.push(c.close);this.skipBr=
!1}}},sortPriority:function(a,b){return a.priority<b.priority?-1:a.priority>b.priority?1:0},applyFilters:function(a,b,c){var d,f;for(f in this.filters)if(this.filters.hasOwnProperty(f)){var g=this.filters[f];g[b]&&"function"===typeof g[b][c]&&(d=g[b][c].apply(g,[this,a]));if(!1===d)return!1}return"undefined"===typeof d?!0:d},reset:function(){this.output=[];this.skipBr=!1}};CKEDITOR.plugins.bbCode.BBCodeRule=function(a,b,c,d){var f=[];this.bbCodeTag=b;this.htmlTag=c;this.editor=a;d&&(this.options=
CKEDITOR.tools.extend({},this.options,d,!0));f.push(m(this.htmlTag));for(a=0;a<this.options.alternativeTags.length;++a)f.push(m(this.options.alternativeTags[a]));this.htmlRegExp=RegExp("^("+f.join("|")+")$","i")};CKEDITOR.plugins.bbCode.BBCodeRule.prototype={bbCodeTag:"",htmlTag:"",htmlRegExp:null,editor:null,options:{priority:-1,renderOpenTagHtml:null,renderCloseTagHtml:null,renderOpenTagBBCode:null,renderCloseTagBBCode:null,brBeforeOpen:!1,brBeforeClose:!1,brAfterOpen:!1,brAfterClose:!1,parseContent:!0,
optionalClose:!0,alternativeTags:[],context:[],children:[],attributes:{}},checkValidBBCodeContext:function(a){var b,c=!1,d=this.options.context.length;if(d){b=0;a:for(;b<this.options.context.length;++b){var d=1,f=this.options.context[b].tag,g=this.options.context[b].maxLevel,e=a.parent;isNaN(g)&&(g=Number.POSITIVE_INFINITY);do{if(e.type!==CKEDITOR.NODE_ELEMENT){c=!f&&0!==g&&g<=d||0===g;continue a}else if(f.bbCodeTag!==e.name){++d;continue}else if(0===g){c=!1;continue a}else if(g>=d){c=!0;continue a}++d}while((e=
e.parent)&&d<=g)}}else c=!0;if(d=this.options.children.length)for(b=0;b<d;++b)var h=this.options.children[b].bbCodeTag,c=a.forEach(function(){return a.type===CKEDITOR.NODE_ELEMENT&&a.name!==h?!1:!0});return this.fire("checkBBCodeContext",{isValid:c,node:a},this.editor).isValid},checkValidHtmlContext:function(a){var b,c=!1,d=this.options.context.length;if(d){b=0;a:for(;b<this.options.context.length;++b){var d=1,f=this.options.context[b].tag,g=this.options.context[b].maxLevel,e=a.parent;isNaN(g)&&(g=
Number.POSITIVE_INFINITY);do{if(e.type!==CKEDITOR.NODE_ELEMENT){c=!f&&0!==g&&g<=d||0===g;continue a}else if(e.name.match(f.htmlRegExp))if(0===g){c=!1;continue a}else{if(g>=d){c=!0;continue a}}else{++d;continue}++d}while((e=e.parent)&&d<=g)}}else c=!0;if((d=this.options.children.length)&&!a.isEmpty)for(b=0;b<d;++b){f=this.options.children[b];for(b=0;b<a.children.length;++b)if(a.children[b].type===CKEDITOR.NODE_ELEMENT&&!a.children[b].name.match(f.htmlRegExp)){c=!1;break}}return this.fire("checkHtmlContext",
{isValid:c,node:a},this.editor).isValid},toBBCode:function(a){var b,c={name:this.bbCodeTag,priority:this.options.priority,renderOpenTag:this.options.renderOpenTagBBCode,renderCloseTag:this.options.renderCloseTagBBCode,brBeforeOpen:this.options.brBeforeOpen,brBeforeClose:this.options.brBeforeClose,brAfterOpen:this.options.brAfterOpen,brAfterClose:this.options.brAfterClose,parseContent:this.options.parseContent,stripContent:this.stripContent,value:null,attributes:{}};if(!1===this.fire("beforeToBBCode",
{node:a,bbCode:c},this.editor))return!1;for(b in this.options.attributes)if(this.options.attributes.hasOwnProperty(b)){var d=this.options.attributes[b],f,g,e;"style"!==b?(f=a.attributes,g=b):d.style?(f=a.attributes.style,g=d.style):(f={style:CKEDITOR.tools.writeCssText(a.attributes.style)},g="style");if(!d.hasOwnProperty("value")&&f[g]){var h=[f[g]];e=f[g];if(!d.hasOwnProperty("htmlPattern")||(h=e.match(d.htmlPattern)))if("function"===typeof d.htmlFormat)e=d.htmlFormat.apply(this,h);else{if("string"===
typeof d.htmlFormat){e=d.htmlFormat;for(var i in h)h.hasOwnProperty(i)&&(e=e.replace(RegExp(m("{"+i+"}"),"g"),h[i]))}}else e=void 0}else d.value===f[g]&&(e=d.value);if("undefined"!==typeof e)!0===d.decode||"undefined"===typeof d.decode?e=CKEDITOR.tools.htmlDecodeAttr(e):"function"===typeof d.decode&&(e=d.decode(e)),"$"===d.map?c.value=e:d.map&&(c.attributes[d.map]=e);else if(d.required)return!1}return this.fire("toBBCode",{node:a,bbCode:c},this.editor).bbCode},toHtml:function(a){var b,c={name:this.htmlTag,
priority:this.options.priority,renderOpenTag:this.options.renderOpenTagHtml,renderCloseTag:this.options.renderCloseTagHtml,brBeforeOpen:this.options.brBeforeOpen,brBeforeClose:this.options.brBeforeClose,brAfterOpen:this.options.brAfterOpen,brAfterClose:this.options.brAfterClose,parseContent:this.options.parseContent,stripContent:this.stripContent,attributes:{style:{}}};if(!1===this.fire("beforeToHtml",{node:a,html:c},this.editor))return!1;for(b in this.options.attributes)if(this.options.attributes.hasOwnProperty(b)){var d=
this.options.attributes[b],f;if(!d.hasOwnProperty("value")&&a.attributes[d.map]){var g=[a.attributes[d.map]];f=a.attributes[d.map];if(!d.hasOwnProperty("bbCodePattern")||(g=f.match(d.bbCodePattern)))if("function"===typeof d.bbCodeFormat)f=d.bbCodeFormat.apply(this,g);else{if("string"===typeof d.bbCodeFormat){f=d.bbCodeFormat;for(var e in g)g.hasOwnProperty(e)&&(f=f.replace(RegExp(m("{"+e+"}"),"g"),g[e]))}}else f=void 0}else f=d.value;if("undefined"!==typeof f)!0===d.encode||"undefined"===typeof d.encode?
f=CKEDITOR.tools.htmlEncodeAttr(f):"function"===typeof d.encode&&(f=d.encode(f)),"style"===b&&d.style?c.attributes.style[d.style]=f:c.attributes[b]=f;else if(d.required)return!1}return this.fire("toHtml",{node:a,html:c},this.editor).html}};CKEDITOR.plugins.bbCode.SmileyFilter=function(a){this.smileyPath=a.config.smiley_path;this.smileyMap={};this.smileyRegExp=[];for(var b=0;b<a.config.smiley_images.length;++b)a.config.smiley_descriptions[b]&&(this.smileyMap[a.config.smiley_descriptions[b]]=a.config.smiley_images[b],
this.smileyRegExp.push(m(a.config.smiley_descriptions[b])));this.smileyRegExp=this.smileyRegExp.length?RegExp("("+this.smileyRegExp.join("|")+")","g"):null};CKEDITOR.plugins.bbCode.SmileyFilter.prototype={bbCode:{beforeParse:function(a,b){var c=b.node,d;if(b.parse&&c.type===CKEDITOR.NODE_ELEMENT&&(d=c.attributes.alt||c.attributes.title)&&"img"===c.name&&this.smileyMap.hasOwnProperty(d))b.node=new CKEDITOR.htmlParser.text(n(d))}},html:{afterParse:function(a,b){var c=b.node;if(this.smileyRegExp&&b.parse&&
c.type===CKEDITOR.NODE_TEXT){var d=this;b.content=b.content.replace(this.smileyRegExp,function(a){var b=CKEDITOR.tools.htmlEncodeAttr(a);return'<img src="'+d.smileyPath+d.smileyMap[a]+'" alt="'+b+'" title="'+b+'"></img>'})}}}};CKEDITOR.event.implementOn(CKEDITOR.plugins.bbCode.BBCodeRule.prototype);CKEDITOR.on("dialogDefinition",function(a){var b=a.data.definition;switch(a.data.name){case "link":b.removeContents("target");b.removeContents("upload");b.removeContents("advanced");a=b.getContents("info");
a.remove("emailSubject");a.remove("emailBody");a.get("linkType").items.splice(1,1);break;case "image":b.removeContents("advanced");a=b.getContents("Link");a.remove("cmbTarget");a=b.getContents("info");a.remove("basic");break;case "videoDialog":a=b.getContents("video"),a.remove("width"),a.remove("height")}});CKEDITOR.plugins.add("bbcode",{requires:["widget","htmlwriter"],version:1,beforeInit:function(a){CKEDITOR.tools.extend(a.config,{basicEntities:!1,entities:!1,fillEmptyBlocks:!1,forcePasteAsPlainText:!0,
font_names:'Georgia/Georgia, serif;Palatino/"Palatino Linotype", "Book Antiqua", Palatino, serif;Times New Roman/"Times New Roman", Times, serif;Arial/Arial, Helvetica, sans-serif;Helvetica/Helvetica, sans-serif;Arial Black/"Arial Black", Gadget, sans-serif;Comic Sans MS/"Comic Sans MS", cursive, sans-serif;Impact/Impact, Charcoal, sans-serif;Lucida Sans/"Lucida Sans Unicode", "Lucida Grande", sans-serif;Tahoma/Tahoma, Geneva, sans-serif;Trebuchet MS/"Trebuchet MS", Helvetica, sans-serif;Verdana/Verdana, Geneva, sans-serif;Courier New/"Courier New", Courier, monospace;Lucida Console/"Lucida Console", Monaco, monospace;',
fontSize_sizes:"25%;50%;75%;100%;125%;175%;200%;225%;275%;300%;"});a.bbCode=new CKEDITOR.plugins.bbCode.BBCode(a)},init:function(a){if(a.elementMode===CKEDITOR.ELEMENT_MODE_INLINE)a.on("contentDom",function(){a.on("setData",function(b){b.data.dataValue=a.bbCode.toHtml(b.data.dataValue)},null,null,1);a.on("toDataFormat",function(b){b.data.dataValue=a.bbCode.toBBCode(b.data.dataValue)},null,null,20)});else a.on("setData",function(b){b.data.dataValue=a.bbCode.toHtml(b.data.dataValue)},null,null,1),a.on("toDataFormat",
function(b){b.data.dataValue=a.bbCode.toBBCode(b.data.dataValue)},null,null,20),a.on("selectionChange",function(a){if((a=a.data.path.block)&&"li"===a.getName()){var c=a.getStyle("text-align");if(c){var d=a.getParent(),f=d.getParent();a.removeStyle("text-align");f.getStyle("text-align")?f.setStyle("text-align",c):d.setStyle("text-align",c)}}})}})})();