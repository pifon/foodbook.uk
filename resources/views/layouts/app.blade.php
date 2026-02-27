<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Foodbook')</title>
    @include('components.build-assets')
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased {{ config('app.debug') ? 'pb-[18rem]' : '' }}">
    <div class="min-h-screen">
        @include('components.header')

        <div class="flex">
            @if(session('api_token'))
                @include('components.sidebar')
            @endif

            <main class="flex-1 px-4 py-8 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-5xl">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    @if(config('app.debug'))
    <style>
    #api-response-log {
        position: fixed !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        z-index: 2147483647 !important;
        height: 14rem;
        min-height: 2.75rem;
        max-height: 80vh;
        background: #374151 !important;
        color: #f3f4f6;
        border-top: 1px solid #4b5563;
        border-radius: 8px 8px 0 0;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.2);
        display: flex;
        flex-direction: column;
        font-family: ui-sans-serif, system-ui, sans-serif;
        transition: height 0.2s ease;
    }
    #api-response-log.collapsed {
        height: 2.75rem;
        min-height: 2.75rem;
    }
    #api-response-log-resize-handle {
        flex-shrink: 0;
        height: 4px;
        cursor: ns-resize;
        background: #4b5563;
    }
    #api-response-log-resize-handle:hover {
        background: #6b7280;
    }
    #api-response-log .api-log-header {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.5rem 0.75rem;
        background: #4b5563;
        border-radius: 8px 8px 0 0;
        cursor: pointer;
        user-select: none;
    }
    #api-response-log .api-log-header:hover {
        background: #555e6b;
    }
    #api-response-log .api-log-header-title {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #fff;
    }
    #api-response-log .api-log-header-btns {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    #api-response-log .api-log-header-btns button {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        border: none;
        background: transparent;
        color: #d1d5db;
        cursor: pointer;
    }
    #api-response-log .api-log-header-btns button:hover {
        background: #6b7280;
        color: #fff;
    }
    #api-response-log .api-log-body {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 0.5rem 0.75rem;
        font-size: 0.8125rem;
        line-height: 1.4;
    }
    #api-response-log .api-log-entry {
        margin-bottom: 0.5rem;
        padding: 0.5rem 0.625rem;
        background: #1f2937;
        border-radius: 6px;
        border-left: 3px solid #6b7280;
    }
    #api-response-log .api-log-entry.success { border-left-color: #34d399; }
    #api-response-log .api-log-entry.error { border-left-color: #f87171; }
    #api-response-log .api-log-entry.warn { border-left-color: #fbbf24; }
    #api-response-log .api-log-entry-summary {
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    #api-response-log .api-log-entry-summary:hover {
        opacity: 0.95;
    }
    #api-response-log .api-log-entry.no-details .api-log-entry-summary {
        cursor: default;
    }
    #api-response-log .api-log-entry-method {
        font-weight: 600;
        color: #9ca3af;
        font-size: 0.7rem;
    }
    #api-response-log .api-log-entry-status {
        font-weight: 600;
        font-size: 0.75rem;
    }
    #api-response-log .api-log-entry-status.ok { color: #34d399; }
    #api-response-log .api-log-entry-status.err { color: #f87171; }
    #api-response-log .api-log-entry-status.warn { color: #fbbf24; }
    #api-response-log .api-log-entry-message {
        color: #e5e7eb;
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    #api-response-log .api-log-entry-details {
        margin-top: 0.375rem;
        padding-top: 0.375rem;
        border-top: 1px solid #374151;
        font-size: 0.75rem;
        color: #9ca3af;
        word-break: break-word;
        white-space: normal;
    }
    #api-response-log .api-log-entry-details .api-log-payload,
    #api-response-log .api-log-entry-details .api-log-response {
        margin-top: 0.25rem;
        color: #9ca3af;
        white-space: pre-wrap;
        word-break: break-all;
    }
    #api-response-log .api-log-entry-details .detail-line {
        margin-top: 0.25rem;
        color: #f87171;
    }
    #api-response-log .api-log-entry.collapsed .api-log-entry-details {
        display: none;
    }
    </style>
    <div id="api-response-log">
        <div id="api-response-log-resize-handle" title="Drag to resize panel"></div>
        <div class="api-log-header" id="api-log-header" title="Click to expand or collapse">
            <span class="api-log-header-title">API responses <span id="api-log-count">(0)</span></span>
            <div class="api-log-header-btns">
                <button type="button" id="api-response-log-clear" title="Clear all">Clear</button>
                <button type="button" id="api-log-toggle" title="Collapse panel" aria-label="Minimize">−</button>
            </div>
        </div>
        <div class="api-log-body" id="api-response-entries"></div>
    </div>
    @endif
    <script>
    window.LARAVEL = window.LARAVEL || {};
    window.LARAVEL.loginUrl = @json(route('login'));
    window.LARAVEL.debug = @json(config('app.debug', false));
    window.redirectIfUnauthorized = function(response) {
        if (response.status === 401 || response.status === 419) {
            var url = window.LARAVEL.loginUrl || '/login';
            response.json().then(function(d) { if (d.redirect) url = d.redirect; window.location.href = url; }).catch(function() { window.location.href = url; });
            return true;
        }
        return false;
    };
    var _apiLogEntries = document.getElementById('api-response-entries');
    var _apiLogPanel = document.getElementById('api-response-log');
    window.captureApiResponse = function(label, url, response, method, requestPayload) {
        if (!window.LARAVEL || !window.LARAVEL.debug) return;
        var entriesEl = _apiLogEntries || document.getElementById('api-response-entries');
        if (!entriesEl) return;
        var logPanel = _apiLogPanel || document.getElementById('api-response-log');
        if (!logPanel) return;
        method = (method || 'GET').toUpperCase();
        var status = response.status;
        var entryKind = status >= 200 && status < 300 ? 'success' : (status >= 400 ? 'error' : 'warn');
        var statusCls = entryKind === 'success' ? 'ok' : (entryKind === 'error' ? 'err' : 'warn');
        var apiRequestLine = response.headers && response.headers.get('X-Api-Request');
        var requestLineText = apiRequestLine ? ('Request: ' + apiRequestLine) : (method + ' ' + url);
        function addBlock(message, detail, requestText, payload) {
            requestText = requestText != null ? requestText : requestLineText;
            var hasExtra = (payload != null && payload !== '') || (detail != null && detail !== '');
            var block = document.createElement('div');
            block.className = 'api-log-entry ' + entryKind + (hasExtra ? ' collapsed' : ' no-details');
            var summary = document.createElement('div');
            summary.className = 'api-log-entry-summary';
            summary.title = hasExtra ? 'Click to expand: request, payload (if any), error detail (if error)' : '';
            var methodSpan = document.createElement('span');
            methodSpan.className = 'api-log-entry-method';
            methodSpan.textContent = method;
            var statusSpan = document.createElement('span');
            statusSpan.className = 'api-log-entry-status ' + statusCls;
            statusSpan.textContent = status;
            var msgSpan = document.createElement('span');
            msgSpan.className = 'api-log-entry-message';
            msgSpan.textContent = (message || '') + (message && url ? ' ' : '') + (url || '');
            summary.appendChild(methodSpan);
            summary.appendChild(statusSpan);
            summary.appendChild(msgSpan);
            block.appendChild(summary);
            var detailLine = null;
            var requestLineEl = null;
            if (hasExtra) {
                var details = document.createElement('div');
                details.className = 'api-log-entry-details';
                requestLineEl = document.createElement('div');
                requestLineEl.textContent = 'Request: ' + requestText;
                details.appendChild(requestLineEl);
                if (payload != null && payload !== '') {
                    var payloadStr = typeof payload === 'string' ? payload : JSON.stringify(payload, null, 2);
                    if (payloadStr.length > 2000) payloadStr = payloadStr.slice(0, 2000) + '\n… [truncated]';
                    var payloadLine = document.createElement('div');
                    payloadLine.className = 'api-log-payload';
                    payloadLine.textContent = 'Payload: ' + payloadStr;
                    details.appendChild(payloadLine);
                }
                detailLine = document.createElement('div');
                detailLine.className = 'detail-line';
                detailLine.style.display = detail != null && detail !== '' ? '' : 'none';
                detailLine.textContent = detail != null && detail !== '' ? 'Error detail: ' + detail : '';
                details.appendChild(detailLine);
                block.appendChild(details);
                summary.addEventListener('click', function(e) {
                    e.stopPropagation();
                    block.classList.toggle('collapsed');
                });
            }
            entriesEl.insertBefore(block, entriesEl.firstChild);
            var countEl = document.getElementById('api-log-count');
            if (countEl) {
                countEl.textContent = '(' + entriesEl.querySelectorAll('.api-log-entry').length + ')';
            }
            return { block: block, msgSpan: msgSpan, detailLine: detailLine, requestLine: requestLineEl, summary: summary, hasDetails: hasExtra, method: method, url: url, responseLine: null };
        }
        function formatResponseBody(body) {
            if (body == null || body === '') return '';
            var str = typeof body === 'string' ? body : JSON.stringify(body);
            try {
                var parsed = JSON.parse(str);
                str = JSON.stringify(parsed, null, 2);
            } catch (e) { /* leave as-is */ }
            if (str.length > 4000) str = str.slice(0, 4000) + '\n… [truncated]';
            return str;
        }
        function updateBlock(ref, message, detail, requestText, responseBody) {
            if (!ref || !ref.msgSpan) return;
            ref.msgSpan.textContent = (message || '') + (message && url ? ' ' : '') + (url || '');
            var hasDetail = detail != null && detail !== '';
            var hasResponse = responseBody != null && responseBody !== '';
            if (ref.hasDetails && ref.detailLine) {
                ref.detailLine.textContent = hasDetail ? ('Error detail: ' + detail) : '';
                ref.detailLine.style.display = hasDetail ? '' : 'none';
            } else if ((hasDetail || hasResponse) && ref.block) {
                ref.block.classList.remove('no-details');
                ref.block.classList.add('collapsed');
                ref.summary.title = 'Click to expand: request, response (if any), error detail (if error)';
                ref.summary.style.cursor = 'pointer';
                var details = document.createElement('div');
                details.className = 'api-log-entry-details';
                var requestLineEl = document.createElement('div');
                requestLineEl.textContent = 'Request: ' + (requestText != null ? requestText : (method + ' ' + url));
                details.appendChild(requestLineEl);
                if (hasResponse) {
                    var responseLineEl = document.createElement('div');
                    responseLineEl.className = 'api-log-response';
                    responseLineEl.textContent = 'Response: ' + formatResponseBody(responseBody);
                    details.appendChild(responseLineEl);
                    ref.responseLine = responseLineEl;
                }
                var detailLine = document.createElement('div');
                detailLine.className = 'detail-line';
                detailLine.textContent = hasDetail ? ('Error detail: ' + detail) : '';
                detailLine.style.display = hasDetail ? '' : 'none';
                details.appendChild(detailLine);
                ref.block.appendChild(details);
                ref.detailLine = detailLine;
                ref.requestLine = requestLineEl;
                ref.hasDetails = true;
                ref.summary.addEventListener('click', function(e) {
                    e.stopPropagation();
                    ref.block.classList.toggle('collapsed');
                });
            }
            if (requestText != null && ref.requestLine) ref.requestLine.textContent = 'Request: ' + requestText;
            if (hasResponse && ref.hasDetails) {
                if (ref.responseLine) {
                    ref.responseLine.textContent = 'Response: ' + formatResponseBody(responseBody);
                } else {
                    var detailsDiv = ref.block.querySelector('.api-log-entry-details');
                    if (detailsDiv) {
                        var responseLineEl = document.createElement('div');
                        responseLineEl.className = 'api-log-response';
                        responseLineEl.textContent = 'Response: ' + formatResponseBody(responseBody);
                        detailsDiv.insertBefore(responseLineEl, ref.detailLine);
                        ref.responseLine = responseLineEl;
                    }
                }
            }
        }
        var ref = addBlock(response.statusText || '(loading…)', null, null, requestPayload);
        _lastApiLogRef = ref;
        try {
            response.clone().text().then(function(body) {
                var message = response.statusText || '';
                var detail = null;
                var requestText = null;
                try {
                    var data = JSON.parse(body);
                    if (data.errors && data.errors[0]) {
                        if (data.errors[0].detail != null) detail = data.errors[0].detail;
                        message = data.errors[0].title || data.errors[0].detail || message;
                    } else if (data.error) {
                        detail = data.error;
                        message = message || data.error;
                    } else if (data.message) {
                        message = data.message;
                    }
                    if (data.meta && data.meta.api_request) requestText = data.meta.api_request;
                } catch (e) { /* use statusText */ }
                updateBlock(ref, message || '(no message)', detail, requestText, body);
            }).catch(function() {
                updateBlock(ref, response.statusText || '(body not readable)', null, null);
            });
        } catch (e) {
            updateBlock(ref, response.statusText || '(body not readable)', null, null);
        }
    };
    var _lastApiLogRef = null;
    window.updateLastApiResponseDetail = function(parsedBody) {
        if (!_lastApiLogRef || !parsedBody) return;
        var ref = _lastApiLogRef;
        var message = '';
        var detail = null;
        var requestText = null;
        if (parsedBody.errors && parsedBody.errors[0]) {
            var err = parsedBody.errors[0];
            if (err.detail != null) detail = err.detail;
            message = err.title || err.detail || message;
        } else if (parsedBody.error) {
            detail = parsedBody.error;
            message = parsedBody.error;
        } else if (parsedBody.message) {
            message = parsedBody.message;
        }
        if (parsedBody.meta && parsedBody.meta.api_request) requestText = parsedBody.meta.api_request;
        if (message && ref.msgSpan) ref.msgSpan.textContent = (message || '') + (ref.url ? ' ' + ref.url : '');
        if (detail != null && detail !== '') {
            if (!ref.hasDetails && ref.block && ref.summary) {
                ref.block.classList.remove('no-details');
                ref.block.classList.add('collapsed');
                ref.summary.title = 'Click to expand: request, payload (if any), error detail (if error)';
                ref.summary.style.cursor = 'pointer';
                var details = document.createElement('div');
                details.className = 'api-log-entry-details';
                var requestLineEl = document.createElement('div');
                requestLineEl.textContent = 'Request: ' + (requestText || (ref.method + ' ' + ref.url));
                details.appendChild(requestLineEl);
                var detailLine = document.createElement('div');
                detailLine.className = 'detail-line';
                detailLine.textContent = 'Error detail: ' + detail;
                details.appendChild(detailLine);
                ref.block.appendChild(details);
                ref.detailLine = detailLine;
                ref.requestLine = requestLineEl;
                ref.hasDetails = true;
                ref.summary.addEventListener('click', function(e) {
                    e.stopPropagation();
                    ref.block.classList.toggle('collapsed');
                });
            } else if (ref.detailLine) {
                ref.detailLine.textContent = 'Error detail: ' + detail;
                ref.detailLine.style.display = '';
            }
        }
        if (requestText != null && ref.requestLine) ref.requestLine.textContent = 'Request: ' + requestText;
    };
    window.addServerApiLogEntries = function(entries) {
        if (!entries || !entries.length) return;
        var entriesEl = document.getElementById('api-response-entries');
        if (!entriesEl) return;
        var method, url, status, message, detail, entryKind, statusCls, block, summary, details, requestLineEl, detailLine, methodSpan, statusSpan, msgSpan, hasExtra;
        for (var i = entries.length - 1; i >= 0; i--) {
            var e = entries[i];
            method = (e.method || 'GET').toUpperCase();
            url = e.url || '';
            status = e.status || 0;
            message = e.message || '(no message)';
            detail = e.detail || null;
            hasExtra = detail != null && detail !== '';
            entryKind = status >= 200 && status < 300 ? 'success' : (status >= 400 ? 'error' : 'warn');
            statusCls = entryKind === 'success' ? 'ok' : (entryKind === 'error' ? 'err' : 'warn');
            block = document.createElement('div');
            block.className = 'api-log-entry ' + entryKind + (hasExtra ? ' collapsed' : ' no-details');
            summary = document.createElement('div');
            summary.className = 'api-log-entry-summary';
            summary.title = hasExtra ? 'Click to expand: request URL, error detail (if error)' : '';
            methodSpan = document.createElement('span');
            methodSpan.className = 'api-log-entry-method';
            methodSpan.textContent = method;
            summary.appendChild(methodSpan);
            statusSpan = document.createElement('span');
            statusSpan.className = 'api-log-entry-status ' + statusCls;
            statusSpan.textContent = status;
            summary.appendChild(statusSpan);
            msgSpan = document.createElement('span');
            msgSpan.className = 'api-log-entry-message';
            msgSpan.textContent = (message ? message + ' ' : '') + url;
            summary.appendChild(msgSpan);
            block.appendChild(summary);
            if (hasExtra) {
                details = document.createElement('div');
                details.className = 'api-log-entry-details';
                requestLineEl = document.createElement('div');
                requestLineEl.textContent = 'Request: ' + method + ' ' + url;
                details.appendChild(requestLineEl);
                detailLine = document.createElement('div');
                detailLine.className = 'detail-line';
                detailLine.textContent = 'Error detail: ' + detail;
                details.appendChild(detailLine);
                block.appendChild(details);
                (function(entryBlock) {
                    summary.addEventListener('click', function(ev) { ev.stopPropagation(); entryBlock.classList.toggle('collapsed'); });
                })(block);
            }
            entriesEl.insertBefore(block, entriesEl.firstChild);
        }
        var countEl = document.getElementById('api-log-count');
        if (countEl) countEl.textContent = '(' + entriesEl.querySelectorAll('.api-log-entry').length + ')';
    };
    (function() {
        if (!window.LARAVEL || !window.LARAVEL.debug || !window.captureApiResponse) return;
        var origFetch = window.fetch;
        window.fetch = function(url, init) {
            init = init || {};
            var method = (init.method || 'GET').toUpperCase();
            var urlStr = (typeof url === 'string') ? url : (url && url.url ? url.url : '');
            var payload = typeof init.body === 'string' ? init.body : undefined;
            var origin = window.location.origin;
            var isSameOrigin = !urlStr || urlStr.indexOf(origin) === 0 || urlStr.charAt(0) === '/';
            var self = this;
            var args = arguments;
            if (!isSameOrigin) return origFetch.apply(self, args);
            return origFetch.apply(self, args).then(function(response) {
                var label = method + ' ' + (urlStr.replace(origin, '') || urlStr);
                if (label.charAt(0) === ' ') label = label.slice(1);
                window.captureApiResponse(label, urlStr, response, method, payload);
                return response;
            });
        };
    })();
    (function() {
        var serverLog = @json($api_log ?? []);
        if (serverLog && serverLog.length && window.addServerApiLogEntries) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() { window.addServerApiLogEntries(serverLog); });
            } else {
                window.addServerApiLogEntries(serverLog);
            }
        }
    })();
    window.syncBodyPaddingForApiBar = function() {
        var panel = document.getElementById('api-response-log');
        if (!panel) return;
        var h = panel.getBoundingClientRect().height;
        document.body.style.paddingBottom = (h + 12) + 'px';
    };
    (function() {
        var resizeHandle = document.getElementById('api-response-log-resize-handle');
        var logPanel = document.getElementById('api-response-log');
        if (resizeHandle && logPanel) {
            var MIN_REM = 2.75;
            var rem = parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;
            function remToPx(r) { return r * rem; }
            function getMaxHeightPx() { return window.innerHeight * 0.8; }
            function setPanelHeight(px) {
                var maxPx = getMaxHeightPx();
                var minPx = remToPx(MIN_REM);
                px = Math.min(maxPx, Math.max(minPx, px));
                logPanel.style.height = px + 'px';
                try { sessionStorage.setItem('api-log-height-px', String(px)); } catch (err) {}
                if (window.syncBodyPaddingForApiBar) window.syncBodyPaddingForApiBar();
            }
            try {
                var saved = sessionStorage.getItem('api-log-height-px');
                if (saved) setPanelHeight(parseFloat(saved));
            } catch (err) {}
            if (window.syncBodyPaddingForApiBar) window.syncBodyPaddingForApiBar();
            document.addEventListener('DOMContentLoaded', function() {
                if (window.syncBodyPaddingForApiBar) window.syncBodyPaddingForApiBar();
            });
            resizeHandle.addEventListener('mousedown', function(ev) {
                ev.preventDefault();
                if (logPanel.classList.contains('collapsed')) logPanel.classList.remove('collapsed');
                var startY = ev.clientY;
                var startH = logPanel.getBoundingClientRect().height;
                function onMove(e) {
                    var dy = startY - e.clientY;
                    setPanelHeight(startH + dy);
                }
                function onUp() {
                    document.removeEventListener('mousemove', onMove);
                    document.removeEventListener('mouseup', onUp);
                }
                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onUp);
            });
        }
    })();
    (function() {
        function getLogPanel() { return document.getElementById('api-response-log'); }
        function getEntries() { return document.getElementById('api-response-entries'); }
        document.body.addEventListener('click', function(e) {
            var panel = getLogPanel();
            if (!panel) return;
            var target = e.target;
            if (target.id === 'api-log-toggle' || target.id === 'api-response-log-clear') {
                e.preventDefault();
                e.stopPropagation();
            }
            if (target.id === 'api-log-toggle') {
                if (panel.classList.contains('collapsed')) {
                    try {
                        var savedH = sessionStorage.getItem('api-log-height-px');
                        if (savedH) panel.style.height = parseFloat(savedH) + 'px';
                        else panel.style.height = '';
                    } catch (err) { panel.style.height = ''; }
                } else {
                    panel.style.height = '2.75rem';
                }
                panel.classList.toggle('collapsed');
                target.textContent = panel.classList.contains('collapsed') ? '+' : '−';
                target.title = panel.classList.contains('collapsed') ? 'Expand panel' : 'Collapse panel';
                if (window.syncBodyPaddingForApiBar) window.syncBodyPaddingForApiBar();
                return;
            }
            if (target.id === 'api-response-log-clear') {
                var entries = getEntries();
                if (entries) {
                    entries.innerHTML = '';
                    var countEl = document.getElementById('api-log-count');
                    if (countEl) countEl.textContent = '(0)';
                }
                return;
            }
            if (target.closest && target.closest('#api-log-header') && !target.closest('.api-log-header-btns')) {
                if (panel.classList.contains('collapsed')) {
                    try {
                        var savedH = sessionStorage.getItem('api-log-height-px');
                        if (savedH) panel.style.height = parseFloat(savedH) + 'px';
                        else panel.style.height = '';
                    } catch (err) { panel.style.height = ''; }
                } else {
                    panel.style.height = '2.75rem';
                }
                panel.classList.toggle('collapsed');
                var btn = document.getElementById('api-log-toggle');
                if (btn) {
                    btn.textContent = panel.classList.contains('collapsed') ? '+' : '−';
                    btn.title = panel.classList.contains('collapsed') ? 'Expand panel' : 'Collapse panel';
                }
                if (window.syncBodyPaddingForApiBar) window.syncBodyPaddingForApiBar();
            }
        });
    })();
    </script>
</body>
</html>
