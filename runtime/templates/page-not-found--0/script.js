/* The entrance itself is the shared system in the global script. All this does
   is arm it, inline as the page parses, so nothing is painted visible and then
   hidden. See the note in runtime/global/scripts.js. */
document.documentElement.classList.add('ia-js');
