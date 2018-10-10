/**
 * Resource jQuery multiple file upload.
 *
 * @package Resources
 * @author CORE Education Ltd
 * @copyright CORE Education Ltd 2010
 * @link http://core-ed.org
 */

// changes form value of that particular file eg don't send if 0
function changeFormValue(id) {
    if (document.getElementById(id).value == '0') {
        document.getElementById(id).value = '1';
    }
    else {
        document.getElementById(id).value = '0';
    }
}

// changes color of the x on the upload file
function changeColor(elm) {
    if (elm.style.color == '') {
        elm.style.color = '#ff0000';
    }
    else {
        elm.style.color = '';
    }
}

