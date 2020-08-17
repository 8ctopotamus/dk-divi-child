// Attorney Profile PDF and Tabs
(function() {
  const { SITE_URL, attorney_name, attorney_title } = wp_data;
  const printBtn = document.getElementById('getsim-print-o-matic');
  const tabs = Array.from(document.querySelectorAll('.tablinks'));
  const tabContent = Array.from(document.querySelectorAll('.tabcontent'));
  const attorneyIMG = document.querySelector('.dk-random-attorney-profile img');
  const logo = document.querySelector('body header:first-of-type img');

  function getBase64Image(img) {
    var canvas = document.createElement("canvas");
    canvas.width = img.width;
    canvas.height = img.height;
    var ctx = canvas.getContext("2d");
    ctx.drawImage(img, 0, 0);
    var dataURL = canvas.toDataURL("image/png");
    return dataURL;
  }
  
  const mapToPDFObject = el => {
    let style = 'body';
    let type = 'text';
    let value = el.innerText;
    const tagName = el.tagName;
    switch(tagName){
      case 'H2':
        style = 'header';
        break;
      case 'H3':
        style = 'bigger';
        break;
      case 'UL':
        type = tagName.toLowerCase();
        value = [...el.children].map(c => c.innerText);
        break;
    }
    return { [type]: value, style };
  };

  const generatePDF = e => {
    e.preventDefault()
    const brandColor = '#829317';
    const mainBody = [...tabContent].reduce((result, el) => {
      Array.from(el.children).map(child => result.push( mapToPDFObject(child) ))
      return result;
    }, []);
    const docDefinition = { 
      footer: function(currentPage, pageCount) { 
        return {
          columns: [
            [
              { 
                text: 'Brookfield - Green Bay - Milwaukee',
                style: 'smaller',
                margins: [20, 0, 0, 20],
              },
              {
                text: SITE_URL, link: SITE_URL,
                style: 'smaller',
                margins: [0, 0, 20, 20],
              },
            ],
            { 
              text: currentPage.toString() + ' of ' + pageCount,
              style: 'smaller',
              alignment: 'right',
              margins: [0, 20, 20, 0],
            },
          ],
        }
      },
      content: [
        {
          image: 'logo',
          width: 80,
          alignment: 'right',
        },
        {
          alignment: 'left',
          columns: [
            { 
              image: 'advisor',
              width: 100,
            },
            [
              { text: attorney_name, style: 'header' },
              { text:  attorney_title, style: 'bigger' },
              ...mainBody,
              
            ]
          ]
        },   
        {
          image: 'logo',
          width: 80,
        },     
      ],
      styles: {
        header: {
          fontSize: 18,
          bold: true
        },
        bigger: {
          fontSize: 15,
          bold: true,
          italics: true,
          color: brandColor,
        },
        body: {
          fontSize: 10,
          marginBottom: 10,
          lineHeight: 1.15,
        },
        smaller: {
          fontSize: 8,
          color: '#999'
        }
      },
      defaultStyle: {
        columnGap: 20
      },
      images: {
        logo: getBase64Image(logo),
        advisor: getBase64Image(attorneyIMG),
      } 
    };
    pdfMake.createPdf(docDefinition).open();
  };

  // change tabs
  function handleTabClick(evt) {
    evt.preventDefault();
    for (let i = 0; i < tabContent.length; i++) {
      tabContent[i].style.display = "none";
    }
    for (let i = 0; i < tabs.length; i++) {
      tabs[i].className = tabs[i].className.replace(" active", "");
    }
    document.getElementById(this.innerText).style.display = "block";
    evt.currentTarget.className += " active";
  };

  // attach listeners
  tabs.forEach(tab => tab.addEventListener('click', handleTabClick));
  printBtn.addEventListener('click', generatePDF);

})();