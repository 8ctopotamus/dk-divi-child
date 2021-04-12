// Resources:
// https://hackernoon.com/how-to-take-screenshots-in-the-browser-using-javascript-l92k3xq7
// https://www.freakyjolly.com/multipage-canvas-pdf-using-jspdf/
// https://stackoverflow.com/questions/36213275/html2canvas-does-not-render-full-div-only-what-is-visible-on-screen#answer-57833406

(function($) {
  const printBtn = document.getElementById('getsim-print-o-matic');
  printBtn.href = '#'

  function getPDF(){
    var HTML_Width = $("body").width();
    var HTML_Height = $("body").height();
    var top_left_margin = 15;
    var PDF_Width = HTML_Width+(top_left_margin*2);
    var PDF_Height = (PDF_Width*1.5)+(top_left_margin*2);
    var canvas_image_width = HTML_Width;
    var canvas_image_height = HTML_Height;
    var totalPDFPages = Math.ceil(HTML_Height/PDF_Height)-1;
	  
    html2canvas(document.body,{
      useCORS: true,
      logging: false,
      height: window.outerHeight + window.innerHeight,
      windowHeight: window.outerHeight + window.innerHeight,
    }).then(function(canvas) {
      canvas.getContext('2d');  
      var imgData = canvas.toDataURL("image/jpeg", 1.0);
      var pdf = new jsPDF('p', 'pt',  [PDF_Width, PDF_Height]);
      pdf.addImage(imgData, 'JPG', top_left_margin, top_left_margin,canvas_image_width,canvas_image_height);
      for (var i = 1; i <= totalPDFPages; i++) { 
        pdf.addPage(PDF_Width, PDF_Height);
        pdf.addImage(imgData, 'JPG', top_left_margin, -(PDF_Height*i)+(top_left_margin*4),canvas_image_width,canvas_image_height);
      }
      pdf.save("screenshot.pdf");
    });
  };

  printBtn.addEventListener('click', getPDF)

})(jQuery)