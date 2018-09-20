function register(){
  const cash = document.getElementById('cashMoney').value
  const number = document.getElementById('phoneno').value
  const randstring = (Math.random() + 1).toString(36).substring(5).toUpperCase()
  console.log(number);
  console.log(cash);
  console.log(randstring);

 const url =  'http://zaka.ticketsoko.com/api/index.php?function=pay'
  var formData = new FormData
  formData.append('phoneNumber',number)
  formData.append('amount',cash)
  formData.append('transactionCode', randstring)

  console.log(formData);

  fetch(url, {
    method: 'POST',
    body: formData
  }).then(function (response) {
     console.log(response);
  });

//   fetch('http://zaka.ticketsoko.com/api/index.php?function=pay', {
//   method: 'post',
//   headers: {
//     'Accept': 'text/html; charset=UTF-8, application/json, text/plain, */*',
//     'Content-Type': 'Content-Type: multipart/form-data',
//     'Access-Control-Allow-Origin': '*'
//   },
//   body:{amount: cash, phoneNumber: number, transactionCode:randstring}
// }).then(res=>res.json())
//   .then(res => console.log(res));

  // axios.post('http://zaka.ticketsoko.com/api/index.php?function=pay', {
  //   amount: cash,
  //   phoneNumber: number,
  //   transactionCode: randstring
  // })
  // .then(function (response) {
  //   console.log(response);
  // })
  // .catch(function (error) {
  //   console.log(error);
  // });
}
