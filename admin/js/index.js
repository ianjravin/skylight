// function login() {
//   const uname = document.getElementById('username').value 
//   const upass = document.getElementById('password').value
//   var formData = new FormData
//   formData.append('username', uname)
//   formData.append('password', upass)

//   axios.post('https://zaka.ticketsoko.com/api/index.php?function=adminLogin', formData)
//   .then((response) => {
//     console.log(response.data);
//     if (response.data.success == true) {
//       window.location.replace('index.html?name='+uname)
      
//     } else {
//       alert(response.data.message)
//     }
    
//   }).catch((err) => {
    
//   });
  
// }

// function getDetails() {
//   let queryString = decodeURIComponent(window.location.search);
//       queryString = queryString.substring(1).split("&");
//       let details = queryString[0].split("=");
//      const name =  decodeURI(details[1]);
//      document.getElementsByClassName("adminName")[0].innerText = name;
//      document.getElementById("adminName").innerText = name;
// }
// window.onload = getDetails;