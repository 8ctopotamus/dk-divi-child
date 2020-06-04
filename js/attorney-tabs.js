(function() {
  const tabs = Array.from(document.querySelectorAll('.tablinks'));
  const tabContent = Array.from(document.querySelectorAll('.tabcontent'));

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
  } 

  tabs.forEach(tab => tab.addEventListener('click', handleTabClick));

})();