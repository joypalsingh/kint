"undefined"==typeof window.kintRichMicrotimeInitialized&&(window.kintRichMicrotimeInitialized=1,window.addEventListener("load",function(){"use strict";var e={},i=Array.prototype.slice.call(document.querySelectorAll("[data-kint-microtime-group]"),0);i.forEach(function(i){if(i.querySelector(".kint-microtime-lap")){var t=i.getAttribute("data-kint-microtime-group"),r=parseFloat(i.querySelector(".kint-microtime-lap").innerHTML),n=parseFloat(i.querySelector(".kint-microtime-avg").innerHTML);"undefined"==typeof e[t]&&(e[t]={}),("undefined"==typeof e[t].min||e[t].min>r)&&(e[t].min=r),("undefined"==typeof e[t].max||e[t].max<r)&&(e[t].max=r),e[t].avg=n}}),i=Array.prototype.slice.call(document.querySelectorAll("[data-kint-microtime-group]>.kint-microtime-lap"),0),i.forEach(function(i){var t,r=i.parentNode.getAttribute("data-kint-microtime-group"),n=parseFloat(i.innerHTML),o=e[r].avg,a=e[r].max,c=e[r].min;i.parentNode.querySelector(".kint-microtime-avg").innerHTML=o,n===o&&n===c&&n===a||(n>o?(t=(n-o)/(a-o),i.style.background="hsl("+(40-40*t)+", 100%, 65%)"):(t=o===c?0:(o-n)/(o-c),i.style.background="hsl("+(40+80*t)+", 100%, 65%)"))})}));
