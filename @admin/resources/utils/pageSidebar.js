export const withSidebar = (callback) => {
  if(document.querySelector('.pageSidebar')) callback();
  else window.$bus.$on('pageSidebar:loaded', callback);
};
