const name = document.getElementById('txtname').value;
const phone = document.getElementById('txtcell').value;
const location = document.getElementById('txtcity').value;
const email = document.getElementById('txtmail').value;
const username = document.getElementById('txtuser').value;
const password = document.getElementById('txtpass').value;
const yob = document.getElementById('txtyear').value;
window.onload(console.log("i am working!!"))

function registerUser(){

  console.log(name)
  axios.get('https://zaka.ticketsoko.com/api/index.php?function=registerInvestors', {
    params: {
      firstName: name,
      phone: phone,
      location: location,
      emailAddress: email,
      user1: username,
      pass: password,
      birthYear: yob

    }
  })
  .then(function (response) {
    console.log(response);
  })
  .catch(function (error) {
    console.log(error);
  });
}
