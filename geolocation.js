/**
 * Shared Geolocation Utility
 * Handles geolocation requests with better error handling and LINE browser detection
 */

(function() {
    'use strict';
    
    // ตรวจสอบว่าเป็น LINE browser หรือไม่
    function isLineBrowser() {
        const userAgent = navigator.userAgent.toLowerCase();
        return userAgent.indexOf('line') !== -1;
    }
    
    // ตรวจสอบว่า geolocation รองรับหรือไม่
    function isGeolocationSupported() {
        return 'geolocation' in navigator;
    }
    
    // ตรวจสอบว่า HTTPS หรือ localhost
    function isSecureContext() {
        return location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
    }
    
    /**
     * Request geolocation with retry mechanism
     * @param {Object} options - Options for geolocation
     * @param {Function} successCallback - Success callback
     * @param {Function} errorCallback - Error callback
     * @param {number} retryCount - Number of retries (default: 2)
     */
    function requestGeolocation(options, successCallback, errorCallback, retryCount = 2) {
        if (!isGeolocationSupported()) {
            errorCallback({
                code: -1,
                message: 'เบราว์เซอร์ของคุณไม่รองรับการระบุตำแหน่ง'
            });
            return;
        }
        
        if (!isSecureContext()) {
            errorCallback({
                code: -2,
                message: 'เว็บไซต์ต้องใช้ HTTPS เพื่อขอตำแหน่ง GPS'
            });
            return;
        }
        
        // ตรวจสอบว่าเป็น LINE browser และแจ้งเตือน
        if (isLineBrowser()) {
            console.warn('⚠️ ตรวจพบ LINE Browser - GPS อาจไม่ทำงาน กรุณาเปิดในเบราว์เซอร์ภายนอก');
        }
        
        const defaultOptions = {
            enableHighAccuracy: true,
            timeout: 20000,
            maximumAge: 0
        };
        
        const finalOptions = Object.assign({}, defaultOptions, options);
        
        let attempts = 0;
        const maxAttempts = retryCount + 1;
        
        function attemptGetPosition() {
            attempts++;
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    successCallback(position);
                },
                function(error) {
                    // ถ้ายังมีโอกาส retry
                    if (attempts < maxAttempts && error.code !== error.PERMISSION_DENIED) {
                        console.log(`Retrying geolocation... (${attempts}/${maxAttempts})`);
                        setTimeout(attemptGetPosition, 2000);
                    } else {
                        errorCallback(error);
                    }
                },
                finalOptions
            );
        }
        
        attemptGetPosition();
    }
    
    /**
     * Get human-readable error message
     */
    function getErrorMessage(error) {
        let message = '';
        let solution = '';
        
        switch (error.code) {
            case error.PERMISSION_DENIED || -1:
                message = 'ผู้ใช้ปฏิเสธการเข้าถึงตำแหน่ง';
                if (isLineBrowser()) {
                    solution = '\n\n⚠️ คุณกำลังใช้ LINE Browser\n\nวิธีแก้:\n1. กดจุดสามจุด (⋮) หรือ Share (□↑) มุมขวาบน\n2. เลือก "เปิดในเบราว์เซอร์" หรือ "Open in Browser"\n3. อนุญาตการเข้าถึงตำแหน่งเมื่อเบราว์เซอร์ถาม\n\nหรือ:\n- iPhone: Settings > LINE > Location > While Using the App\n- Android: Settings > Apps > LINE > Permissions > Location > Allow';
                } else {
                    solution = '\n\nวิธีแก้:\n1. ตรวจสอบว่าได้กด "Allow" เมื่อเบราว์เซอร์ถามสิทธิ์การเข้าถึงตำแหน่ง\n2. ตรวจสอบการตั้งค่าความเป็นส่วนตัวของเบราว์เซอร์\n3. ลองรีเฟรชหน้าเว็บและอนุญาตอีกครั้ง';
                }
                break;
                
            case error.POSITION_UNAVAILABLE:
                message = 'ไม่สามารถระบุตำแหน่งได้';
                solution = '\n\nวิธีแก้:\n1. เปิด Location (GPS) Mode บนอุปกรณ์\n2. ตรวจสอบว่ามีสัญญาณอินเทอร์เน็ต\n3. ถ้าใช้ในอาคาร ลองย้ายไปที่โล่งแจ้ง\n4. ตรวจสอบว่า GPS เปิดอยู่ (Settings > Location)';
                break;
                
            case error.TIMEOUT:
                message = 'หมดเวลาในการขอตำแหน่ง';
                solution = '\n\nวิธีแก้:\n1. เปิด GPS และอินเทอร์เน็ตพร้อมกัน\n2. ย้ายไปที่โล่งแจ้งเพื่อรับสัญญาณ GPS ได้ดีขึ้น\n3. ลองใหม่อีกครั้ง';
                break;
                
            case -2:
                message = 'เว็บไซต์ต้องใช้ HTTPS';
                solution = '\n\nเว็บไซต์นี้ต้องใช้ HTTPS เพื่อความปลอดภัยในการขอตำแหน่ง';
                break;
                
            default:
                message = 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ';
                solution = '\n\nวิธีแก้:\n1. ตรวจสอบว่าเว็บทำงานผ่าน HTTPS\n2. ตรวจสอบว่าไม่โดนบล็อกโดย AdBlock หรือ Security App\n3. ลองรีเฟรชหน้าเว็บ\n4. ลองเปิดในเบราว์เซอร์อื่น';
        }
        
        return message + solution + '\n\n(Error Code: ' + error.code + ')';
    }
    
    /**
     * Reverse geocoding - Convert coordinates to address
     */
    function reverseGeocode(lat, lon) {
        return fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=18&addressdetails=1`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                return data.display_name || 'ไม่ทราบชื่อสถานที่';
            })
            .catch(error => {
                console.error('Reverse geocoding error:', error);
                return 'ไม่ทราบชื่อสถานที่';
            });
    }
    
    // Export functions to window object
    window.GeolocationUtil = {
        request: requestGeolocation,
        getErrorMessage: getErrorMessage,
        reverseGeocode: reverseGeocode,
        isLineBrowser: isLineBrowser,
        isSupported: isGeolocationSupported,
        isSecure: isSecureContext
    };
})();

