alertify
  .okBtn("Confirm")
  .cancelBtn("Cancel")
  .confirm("Are you sure you want to accept the offer?", (ev) => {

    
      ev.preventDefault();
      alertify.success("You've clicked OK");

  }, (ev) => {

    
      ev.preventDefault();

      alertify.error("You've clicked Cancel");

  });