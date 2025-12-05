/**
 * Shared Geolocation Utility
 * Handles geolocation requests with better error handling and LINE browser detection
 * Enhanced for Android devices with improved permission handling
 */

(function() {
    'use strict';
    
    // ตรวจสอบว่าเป็น LINE browser หรือไม่
    function isLineBrowser() {
        const userAgent = navigator.userAgent.toLowerCase();
        return userAgent.indexOf('line') !== -1;
    }
    
    // ตรวจสอบว่าเป็น Android หรือไม่
    function isAndroid() {
        const userAgent = navigator.userAgent.toLowerCase();
        return userAgent.indexOf('android') !== -1;
    }
    
    // ตรวจสอบว่าเป็น iOS หรือไม่
    function isIOS() {
        const userAgent = navigator.userAgent.toLowerCase();
        return /iphone|ipad|ipod/.test(userAgent);
    }
    
    // ตรวจสอบว่า geolocation รองรับหรือไม่
    function isGeolocationSupported() {
        return 'geolocation' in navigator;
    }
    
    // ตรวจสอบว่า HTTPS หรือ localhost
    function isSecureContext() {
        return location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
    }
    
    // ตรวจสอบ permission state (ถ้ารองรับ)
    function checkPermissionState() {
        if (navigator.permissions && navigator.permissions.query) {
            return navigator.permissions.query({ name: 'geolocation' })
                .then(result => result.state)
                .catch(() => 'unknown');
        }
        return Promise.resolve('unknown');
    }
    
    /**
     * Request geolocation with retry mechanism and Android-specific handling
     * @param {Object} options - Options for geolocation
     * @param {Function} successCallback - Success callback
     * @param {Function} errorCallback - Error callback
     * @param {number} retryCount - Number of retries (default: 3 for Android, 2 for others)
     */
    function requestGeolocation(options, successCallback, errorCallback, retryCount = null) {
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
        
        // ตรวจสอบว่าเป็น LINE browser และแจ้งเตือน (UI notification will be shown by calling page)
        if (isLineBrowser()) {
            // Trigger custom event for UI notification
            if (typeof window !== 'undefined' && window.dispatchEvent) {
                window.dispatchEvent(new CustomEvent('lineBrowserDetected', {
                    detail: { message: '⚠️ ตรวจพบ LINE Browser - GPS อาจไม่ทำงาน กรุณาเปิดในเบราว์เซอร์ภายนอก' }
                }));
            }
        }
        
        // Android-specific settings
        const androidRetryCount = retryCount !== null ? retryCount : (isAndroid() ? 3 : 2);
        const androidTimeout = isAndroid() ? 30000 : 20000; // Longer timeout for Android
        
        const defaultOptions = {
            enableHighAccuracy: true,
            timeout: androidTimeout,
            maximumAge: 0
        };
        
        const finalOptions = Object.assign({}, defaultOptions, options);
        
        let attempts = 0;
        const maxAttempts = androidRetryCount + 1;
        let watchId = null;
        let fallbackAttempted = false;
        
        // Function to try with watchPosition as fallback (Android-specific)
        function tryWatchPosition() {
            if (watchId !== null) return; // Already watching
            
            // Silent retry - no console log
            watchId = navigator.geolocation.watchPosition(
                function(position) {
                    // Success! Clear watch and call success callback
                    if (watchId !== null) {
                        navigator.geolocation.clearWatch(watchId);
                        watchId = null;
                    }
                    successCallback(position);
                },
                function(error) {
                    // Watch also failed, continue with normal error handling
                    if (watchId !== null) {
                        navigator.geolocation.clearWatch(watchId);
                        watchId = null;
                    }
                    handleError(error);
                },
                finalOptions
            );
            
            // Clear watch after timeout if still watching
            setTimeout(function() {
                if (watchId !== null) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                }
            }, finalOptions.timeout);
        }
        
        function attemptGetPosition(useHighAccuracy = true) {
            attempts++;
            
            const attemptOptions = Object.assign({}, finalOptions);
            if (!useHighAccuracy) {
                attemptOptions.enableHighAccuracy = false;
                // Silent retry with lower accuracy
            }
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    // Clear watch if active
                    if (watchId !== null) {
                        navigator.geolocation.clearWatch(watchId);
                        watchId = null;
                    }
                    successCallback(position);
                },
                function(error) {
                    handleError(error, useHighAccuracy);
                },
                attemptOptions
            );
        }
        
        function handleError(error, useHighAccuracy = true) {
            // If permission denied, don't retry
            if (error.code === error.PERMISSION_DENIED) {
                if (watchId !== null) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                }
                errorCallback(error);
                return;
            }
            
            // For Android: Try watchPosition as fallback if getCurrentPosition fails
            if (isAndroid() && !fallbackAttempted && attempts >= 2) {
                fallbackAttempted = true;
                tryWatchPosition();
                return;
            }
            
            // Try with lower accuracy if high accuracy failed (Android-specific)
            if (isAndroid() && useHighAccuracy && attempts >= 2 && !fallbackAttempted) {
                fallbackAttempted = true;
                setTimeout(function() {
                    attemptGetPosition(false);
                }, 2000);
                return;
            }
            
            // Normal retry logic
            if (attempts < maxAttempts) {
                const delay = isAndroid() ? 3000 : 2000; // Longer delay for Android
                // Silent retry - no console log
                setTimeout(function() {
                    attemptGetPosition(useHighAccuracy);
                }, delay);
            } else {
                // All retries exhausted, try watchPosition as last resort (Android)
                if (isAndroid() && !fallbackAttempted) {
                    tryWatchPosition();
                } else {
                    if (watchId !== null) {
                        navigator.geolocation.clearWatch(watchId);
                        watchId = null;
                    }
                    errorCallback(error);
                }
            }
        }
        
        // Check permission state first (if available)
        checkPermissionState().then(state => {
            if (state === 'denied') {
                errorCallback({
                    code: 1, // PERMISSION_DENIED
                    message: 'การเข้าถึงตำแหน่งถูกปฏิเสธ'
                });
                return;
            }
            
            // Start the first attempt
            attemptGetPosition();
        }).catch(() => {
            // If permission API not available, just start
            attemptGetPosition();
        });
        
        // Handle page visibility changes (user switched to external browser)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden && watchId === null && attempts < maxAttempts) {
                // Page became visible again, might be in external browser now
                // Trigger custom event for UI notification instead of console log
                if (typeof window !== 'undefined' && window.dispatchEvent) {
                    window.dispatchEvent(new CustomEvent('geolocationRetry', {
                        detail: { message: '📱 กำลังลองขอตำแหน่งอีกครั้ง...' }
                    }));
                }
                setTimeout(function() {
                    if (!window.gpsReady) {
                        attemptGetPosition();
                    }
                }, 1000);
            }
        });
    }
    
    /**
     * Get human-readable error message
     */
    function getErrorMessage(error) {
        let message = '';
        let solution = '';
        
        // Fix: Properly check error codes
        const errorCode = error.code;
        
        if (errorCode === 1 || errorCode === error.PERMISSION_DENIED || errorCode === -1) {
            message = 'ผู้ใช้ปฏิเสธการเข้าถึงตำแหน่ง';
            if (isLineBrowser()) {
                if (isAndroid()) {
                    solution = '\n\n⚠️ คุณกำลังใช้ LINE Browser บน Android\n\nวิธีแก้:\n1. กดจุดสามจุด (⋮) มุมขวาบน\n2. เลือก "เปิดในเบราว์เซอร์" หรือ "Open in Browser"\n3. เลือก Chrome\n4. อนุญาตการเข้าถึงตำแหน่งเมื่อ Chrome ถาม\n\nหรือตั้งค่า:\n- Settings > Apps > LINE > Permissions > Location > Allow\n- Settings > Location > On';
                } else if (isIOS()) {
                    solution = '\n\n⚠️ คุณกำลังใช้ LINE Browser บน iOS\n\nวิธีแก้:\n1. กดไอคอน Share (□↑) มุมขวาบน\n2. เลือก "Safari" หรือ "เปิดในเบราว์เซอร์"\n3. อนุญาตการเข้าถึงตำแหน่งเมื่อ Safari ถาม\n\nหรือตั้งค่า:\n- Settings > LINE > Location > While Using the App';
                } else {
                    solution = '\n\n⚠️ คุณกำลังใช้ LINE Browser\n\nวิธีแก้:\n1. กดเมนู (⋮) หรือ Share (□↑) มุมขวาบน\n2. เลือก "เปิดในเบราว์เซอร์" หรือ "Open in Browser"\n3. อนุญาตการเข้าถึงตำแหน่งเมื่อเบราว์เซอร์ถาม';
                }
            } else {
                if (isAndroid()) {
                    solution = '\n\nวิธีแก้สำหรับ Android:\n1. ตรวจสอบว่าได้กด "Allow" เมื่อ Chrome ถามสิทธิ์การเข้าถึงตำแหน่ง\n2. ไปที่ Settings > Apps > Chrome > Permissions > Location > Allow\n3. ตรวจสอบว่า Location (GPS) เปิดอยู่: Settings > Location > On\n4. ลองรีเฟรชหน้าเว็บและอนุญาตอีกครั้ง\n5. ถ้ายังไม่ได้ ลองปิด Chrome แล้วเปิดใหม่';
                } else {
                    solution = '\n\nวิธีแก้:\n1. ตรวจสอบว่าได้กด "Allow" เมื่อเบราว์เซอร์ถามสิทธิ์การเข้าถึงตำแหน่ง\n2. ตรวจสอบการตั้งค่าความเป็นส่วนตัวของเบราว์เซอร์\n3. ลองรีเฟรชหน้าเว็บและอนุญาตอีกครั้ง';
                }
            }
        } else if (errorCode === 2 || errorCode === error.POSITION_UNAVAILABLE) {
                
            message = 'ไม่สามารถระบุตำแหน่งได้';
            if (isAndroid()) {
                solution = '\n\nวิธีแก้สำหรับ Android:\n1. เปิด Location (GPS) Mode: Settings > Location > On\n2. เลือก "High accuracy" mode (ใช้ GPS + Wi-Fi + Mobile networks)\n3. ตรวจสอบว่ามีสัญญาณอินเทอร์เน็ต\n4. ถ้าใช้ในอาคาร ลองย้ายไปที่โล่งแจ้ง\n5. ตรวจสอบว่า GPS เปิดอยู่: Settings > Location > Mode > High accuracy\n6. ลองปิดแล้วเปิด Location อีกครั้ง';
            } else {
                solution = '\n\nวิธีแก้:\n1. เปิด Location (GPS) Mode บนอุปกรณ์\n2. ตรวจสอบว่ามีสัญญาณอินเทอร์เน็ต\n3. ถ้าใช้ในอาคาร ลองย้ายไปที่โล่งแจ้ง\n4. ตรวจสอบว่า GPS เปิดอยู่ (Settings > Location)';
            }
        } else if (errorCode === 3 || errorCode === error.TIMEOUT) {
            message = 'หมดเวลาในการขอตำแหน่ง';
            if (isAndroid()) {
                solution = '\n\nวิธีแก้สำหรับ Android:\n1. เปิด GPS และอินเทอร์เน็ตพร้อมกัน\n2. ไปที่ Settings > Location > Mode > High accuracy\n3. ย้ายไปที่โล่งแจ้งเพื่อรับสัญญาณ GPS ได้ดีขึ้น\n4. ตรวจสอบว่า Wi-Fi หรือ Mobile data เปิดอยู่\n5. ลองปิดแล้วเปิด Location อีกครั้ง\n6. ลองใหม่อีกครั้ง (ระบบจะลองใหม่อัตโนมัติ)';
            } else {
                solution = '\n\nวิธีแก้:\n1. เปิด GPS และอินเทอร์เน็ตพร้อมกัน\n2. ย้ายไปที่โล่งแจ้งเพื่อรับสัญญาณ GPS ได้ดีขึ้น\n3. ลองใหม่อีกครั้ง';
            }
        } else if (errorCode === -2) {
            message = 'เว็บไซต์ต้องใช้ HTTPS';
            solution = '\n\nเว็บไซต์นี้ต้องใช้ HTTPS เพื่อความปลอดภัยในการขอตำแหน่ง';
        } else {
            message = 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ';
            if (isAndroid()) {
                solution = '\n\nวิธีแก้สำหรับ Android:\n1. ตรวจสอบว่าเว็บทำงานผ่าน HTTPS\n2. ตรวจสอบว่าไม่โดนบล็อกโดย AdBlock หรือ Security App\n3. ลองรีเฟรชหน้าเว็บ\n4. ตรวจสอบการตั้งค่า Location: Settings > Location > Mode > High accuracy\n5. ลองเปิดในเบราว์เซอร์อื่น (Chrome)';
            } else {
                solution = '\n\nวิธีแก้:\n1. ตรวจสอบว่าเว็บทำงานผ่าน HTTPS\n2. ตรวจสอบว่าไม่โดนบล็อกโดย AdBlock หรือ Security App\n3. ลองรีเฟรชหน้าเว็บ\n4. ลองเปิดในเบราว์เซอร์อื่น';
            }
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
                // Silent error - return default location name
                return 'ไม่ทราบชื่อสถานที่';
            });
    }
    
    // Export functions to window object
    window.GeolocationUtil = {
        request: requestGeolocation,
        getErrorMessage: getErrorMessage,
        reverseGeocode: reverseGeocode,
        isLineBrowser: isLineBrowser,
        isAndroid: isAndroid,
        isIOS: isIOS,
        isSupported: isGeolocationSupported,
        isSecure: isSecureContext,
        checkPermission: checkPermissionState
    };
    
    // Global flag to track if GPS is ready (for visibility change handler)
    window.gpsReady = false;
})();

