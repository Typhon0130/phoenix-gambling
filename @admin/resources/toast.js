import * as iziToast from "izitoast";

class Toast {

  success(message) {
    iziToast.success({
      message: message,
      position: 'topRight'
    });
  }

  error(message) {
    iziToast.error({
      message: message,
      position: 'topRight'
    });
  }

}

export default new Toast();
