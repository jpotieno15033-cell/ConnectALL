(function () {
    function initConnectAllInjections() {
        // 1. INJECT THE FORGOT PASSWORD LINK on the login screen
        // Look for the password input field on the login form
        const passwordInput = document.querySelector("input[name='password']");
        if (passwordInput && !document.querySelector("#js-forgot-link")) {
            const forgotWrap = document.createElement("p");
            forgotWrap.id = "js-forgot-link";
            forgotWrap.style.margin = "6px 0 12px 0";
            forgotWrap.style.fontSize = "13px";
            forgotWrap.style.textAlign = "right";

            const forgotLink = document.createElement("a");
            forgotLink.href = "forgot.php";
            forgotLink.innerText = "Forgot Password?";
            forgotLink.style.color = "#64748b";
            forgotLink.style.fontWeight = "600";
            forgotLink.style.textDecoration = "none";
            forgotLink.onmouseover = function() { this.style.color = "#1d4ed8"; };
            forgotLink.onmouseout = function() { this.style.color = "#64748b"; };

            forgotWrap.appendChild(forgotLink);
            // Insert the link right after the password input field
            passwordInput.parentNode.insertBefore(forgotWrap, passwordInput.nextSibling);
        }

        // 2. INJECT THE MESSENGER BUTTON on the main timeline header
        const headerContainer = document.querySelector(".app-header div");
        if (headerContainer && !document.querySelector("#js-messenger-btn")) {
            const msgBtn = document.createElement("a");
            msgBtn.id = "js-messenger-btn";
            msgBtn.href = "messages.php";
            msgBtn.innerText = "💬 Messenger";
            msgBtn.style.color = "#ffffff";
            msgBtn.style.fontSize = "13px";
            msgBtn.style.fontWeight = "700";
            msgBtn.style.textDecoration = "none";
            msgBtn.style.background = "#6366f1";
            msgBtn.style.padding = "6px 14px";
            msgBtn.style.borderRadius = "20px";
            msgBtn.style.marginRight = "12px";
            msgBtn.style.display = "inline-flex";
            msgBtn.style.alignItems = "center";
            msgBtn.style.boxShadow = "0 4px 6px rgba(99, 102, 241, 0.15)";
            msgBtn.style.transition = "all 0.2s ease-in-out";
            msgBtn.style.cursor = "pointer";
            
            msgBtn.onmouseover = function() { this.style.background = "#4f46e5"; this.style.transform = "translateY(-1px)"; };
            msgBtn.onmouseout = function() { this.style.background = "#6366f1"; this.style.transform = "translateY(0px)"; };
            headerContainer.insertBefore(msgBtn, headerContainer.firstChild);
        }
    }

    // Keep looking for the forms until the page is fully loaded
    const checkInterval = setInterval(function() {
        initConnectAllInjections();
        if (document.readyState === "complete") {
            clearInterval(checkInterval);
        }
    }, 100);
})();
