// Attorney Profile PDF and Tabs
(function() {
  const { SITE_URL, attorney_name, attorney_title } = wp_data
  const printBtn = document.getElementById('getsim-print-o-matic')
  const tabs = Array.from(document.querySelectorAll('.tablinks'));
  const tabContent = Array.from(document.querySelectorAll('.tabcontent'));
  const attorneyProfile = document.querySelector('.dk-random-attorney-profile')
  const attorneyIMG = document.querySelector('.dk-random-attorney-profile img')
  const logo = document.querySelector('body header:first-of-type img')
  const tabContentSections = Array.from(document.querySelectorAll('.tabcontent section'));

  function getBase64Image(img) {
    var canvas = document.createElement("canvas");
    canvas.width = img.width;
    canvas.height = img.height;
    var ctx = canvas.getContext("2d");
    ctx.drawImage(img, 0, 0);
    var dataURL = canvas.toDataURL("image/png");
    return dataURL;
  }
  
  const elToObj = el => {
    console.log(el)
    let style
    let type
    switch(el.tagName){
      case 'H2':
        style = 'header';
        break;
      case 'H3':
        style = 'bigger';
        break;
      default:
        type = 'text'
        style = 'body'
    }
    return { [type]: el.innerText, style }

    // {
		// 	ul: [
		// 		'item 1',
		// 		'item 2',
		// 		'item 3'
		// 	]
		// },
  }

  function generatePDF(e) {
    e.preventDefault()
    
    const mainBody = [...tabContent]
      .reduce((result, el) => {
        Array.from(el.children)
          .map(child => result.push( elToObj(child) ))
        return result;
      }, []);

    const docDefinition = { 
      content: [
        {
          image: getBase64Image(logo),
          fit: [100, 100],
          // style: {marginLeft: 'auto'}
        },
        {
          alignment: 'left',
          columns: [
            { 
              image: getBase64Image(attorneyIMG),
              width: 120,
            },
            [
              { text: attorney_name, style: 'header' },
              { text:  attorney_title },
              ...mainBody,
              { text: 'Brookfield │ Green Bay │ Milwaukee' },
              { text: SITE_URL, link: SITE_URL },
            ]
          ]
        },
      ],
      styles: {
        header: {
          fontSize: 18,
          bold: true
        },
        bigger: {
          fontSize: 15,
          italics: true
        },
        body: {
          fontSize: 12,
          marginBottom: 10,
        }
      },
      defaultStyle: {
        columnGap: 20
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