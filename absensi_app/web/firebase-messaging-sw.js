importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js');

firebase.initializeApp({
  apiKey: "AIzaSyBRR56Y8462BDkPM6V32DNjeORpIH3puxY",
  authDomain: "absensi-euphorbia.firebaseapp.com",
  projectId: "absensi-euphorbia",
  storageBucket: "absensi-euphorbia.firebasestorage.app",
  messagingSenderId: "473541374434",
  appId: "1:473541374434:web:8d5f79522b166446d049bb",
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
  console.log('Background message received:', payload);

  const notificationTitle = payload.notification.title;
  const notificationOptions = {
    body: payload.notification.body,
    icon: '/icons/Icon-192.png',
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});