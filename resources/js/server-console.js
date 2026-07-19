import { FitAddon } from '@xterm/addon-fit';
import { Terminal } from '@xterm/xterm';
import '@xterm/xterm/css/xterm.css';

const operations = document.querySelector('[data-server-operations]');
const root = document.querySelector('[data-server-console]');

if (operations && root) {
    const sectionButtons = [...operations.querySelectorAll('[data-server-section]')];
    const sectionPanels = [...operations.querySelectorAll('[data-server-section-panel]')];
    const terminalElement = root.querySelector('[data-console-terminal]');
    const state = root.querySelector('[data-console-state]');
    const connectButton = root.querySelector('[data-console-connect]');
    const disconnectButton = root.querySelector('[data-console-disconnect]');
    const fullscreenButton = root.querySelector('[data-console-fullscreen]');
    const modalElement = document.querySelector('[data-console-auth-modal]');
    const form = modalElement?.querySelector('[data-console-auth-form]');
    const usernameInput = modalElement?.querySelector('[data-console-username]');
    const passwordInput = modalElement?.querySelector('[data-console-password]');
    const authError = modalElement?.querySelector('[data-console-auth-error]');
    const submitButton = modalElement?.querySelector('[data-console-auth-submit]');
    const modal = modalElement ? window.bootstrap.Modal.getOrCreateInstance(modalElement) : null;
    const fitAddon = new FitAddon();
    const terminal = new Terminal({
        allowProposedApi: false,
        convertEol: true,
        cursorBlink: true,
        cursorStyle: 'block',
        fontFamily: '"JetBrains Mono", Consolas, monospace',
        fontSize: 12,
        fontWeight: '600',
        scrollback: 5000,
        theme: {
            background: '#080b10',
            foreground: '#e8edf5',
            cursor: '#6ea8fe',
            selectionBackground: '#284b78',
            black: '#111827',
            brightBlack: '#64748b',
            blue: '#60a5fa',
            brightBlue: '#93c5fd',
            green: '#34d399',
            brightGreen: '#6ee7b7',
            red: '#fb7185',
            brightRed: '#fda4af',
            yellow: '#fbbf24',
            brightYellow: '#fde68a',
        },
    });
    let socket = null;
    let connected = false;
    let connecting = false;
    let intentionalClose = false;

    terminal.loadAddon(fitAddon);
    terminal.open(terminalElement);
    terminal.writeln(`\x1b[90m${root.dataset.disconnectedLabel || 'Session closed'}\x1b[0m`);

    const websocketUrl = (configuredUrl) => {
        const url = new URL(configuredUrl, window.location.href);
        url.protocol = url.protocol === 'https:' ? 'wss:' : 'ws:';
        return url.toString();
    };

    const setState = (kind, label) => {
        state.classList.remove('is-disconnected', 'is-connecting', 'is-connected');
        state.classList.add(`is-${kind}`);
        state.querySelector('span').textContent = label;
    };

    const showError = (message) => {
        authError.textContent = message;
        authError.hidden = false;
    };

    const resetButtons = () => {
        connectButton.hidden = connected || connecting;
        disconnectButton.hidden = !connected && !connecting;
    };

    const sendInput = (data) => {
        if (connected && typeof data === 'string' && data && socket?.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify({ type: 'input', data }));
        }
    };

    const closeSession = (message = null) => {
        intentionalClose = true;
        if (socket?.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify({ type: 'disconnect' }));
            socket.close(1000, 'Page left');
        } else {
            socket?.close();
        }
        socket = null;
        connected = false;
        connecting = false;
        passwordInput.value = '';
        setState('disconnected', root.dataset.disconnectedLabel || 'Session closed');
        resetButtons();
        terminal.reset();
        terminal.writeln(`\x1b[90m${message || root.dataset.disconnectedLabel || 'Session closed'}\x1b[0m`);
    };

    const openAuthentication = () => {
        authError.hidden = true;
        passwordInput.value = '';
        if (root.dataset.enabled !== 'true') {
            const message = root.dataset.unavailableLabel || 'Console unavailable';
            setState('disconnected', message);
            terminal.writeln(`\r\n\x1b[31m${message}\x1b[0m`);
            return;
        }
        modal?.show();
        modalElement?.addEventListener('shown.bs.modal', () => usernameInput.focus(), { once: true });
    };

    const switchSection = (section) => {
        sectionButtons.forEach((button) => {
            const active = button.dataset.serverSection === section;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        sectionPanels.forEach((panel) => { panel.hidden = panel.dataset.serverSectionPanel !== section; });

        if (section === 'console') {
            requestAnimationFrame(() => {
                fitAddon.fit();
                if (!connected && !connecting) openAuthentication();
            });
        } else if (connected || connecting) {
            closeSession();
        }
    };

    sectionButtons.forEach((button) => button.addEventListener('click', () => switchSection(button.dataset.serverSection)));
    connectButton.addEventListener('click', openAuthentication);
    disconnectButton.addEventListener('click', () => closeSession());

    terminal.onData(sendInput);

    terminalElement.addEventListener('contextmenu', async (event) => {
        event.preventDefault();
        if (!connected) return;

        try {
            if (terminal.hasSelection()) {
                await navigator.clipboard.writeText(terminal.getSelection());
                terminal.clearSelection();
            } else {
                sendInput(await navigator.clipboard.readText());
            }
        } catch {
            // Clipboard permissions remain controlled by the browser.
        } finally {
            terminal.focus();
        }
    });

    const syncFullscreenButton = () => {
        const fullscreen = document.fullscreenElement === root;
        const label = fullscreen
            ? root.dataset.exitFullscreenLabel || 'Exit full screen'
            : root.dataset.fullscreenLabel || 'Full screen';
        fullscreenButton.querySelector('i').className = `fa-solid ${fullscreen ? 'fa-compress' : 'fa-expand'}`;
        fullscreenButton.title = label;
        fullscreenButton.setAttribute('aria-label', label);
        requestAnimationFrame(() => {
            resize();
            terminal.scrollToBottom();
            terminal.focus();
        });
    };

    fullscreenButton.addEventListener('click', async () => {
        try {
            if (document.fullscreenElement === root) await document.exitFullscreen();
            else await root.requestFullscreen();
        } catch {
            // Fullscreen availability is controlled by the browser.
        }
    });
    document.addEventListener('fullscreenchange', syncFullscreenButton);

    const resize = () => {
        if (operations.querySelector('[data-server-section="console"]')?.classList.contains('active')) fitAddon.fit();
        if (connected && socket?.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify({ type: 'resize', cols: terminal.cols, rows: terminal.rows }));
        }
    };
    new ResizeObserver(resize).observe(terminalElement);

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        authError.hidden = true;
        submitButton.disabled = true;
        connecting = true;
        intentionalClose = false;
        setState('connecting', root.dataset.connectingLabel || 'Connecting');
        resetButtons();

        const credentials = {
            username: usernameInput.value.trim(),
            password: passwordInput.value,
        };
        passwordInput.value = '';

        try {
            const response = await fetch(root.dataset.ticketEndpoint, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: '{}',
            });
            if (!response.ok) throw new Error('ticket_failed');

            const authorization = await response.json();
            if (credentials.username !== authorization.username) throw new Error('authentication_failed');

            socket = new WebSocket(websocketUrl(authorization.gateway_url));
            socket.addEventListener('open', () => {
                socket.send(JSON.stringify({
                    type: 'authenticate',
                    ticket: authorization.ticket,
                    username: credentials.username,
                    password: credentials.password,
                    cols: terminal.cols,
                    rows: terminal.rows,
                }));
                credentials.password = '';
            });
            socket.addEventListener('message', (socketEvent) => {
                let message;
                try {
                    message = JSON.parse(socketEvent.data);
                } catch {
                    socket.close(4002, 'Invalid gateway message');
                    return;
                }
                if (message.type === 'ready') {
                    connected = true;
                    connecting = false;
                    modal?.hide();
                    setState('connected', (root.dataset.connectedLabel || 'Connected as :user').replace(':user', message.username));
                    resetButtons();
                    terminal.reset();
                    terminal.focus();
                    resize();
                } else if (message.type === 'output') {
                    const bytes = Uint8Array.from(atob(message.data), (character) => character.charCodeAt(0));
                    terminal.write(bytes);
                } else if (message.type === 'error') {
                    if (connecting) showError(root.dataset.authenticationError || 'Authentication failed');
                    socket.close(4003, message.code || 'Authentication failed');
                }
            });
            socket.addEventListener('close', () => {
                const wasConnecting = connecting;
                socket = null;
                connected = false;
                connecting = false;
                setState('disconnected', root.dataset.disconnectedLabel || 'Session closed');
                resetButtons();
                if (wasConnecting && !intentionalClose) {
                    showError(root.dataset.authenticationError || 'Authentication failed');
                    modal?.show();
                } else if (!intentionalClose) {
                    terminal.writeln(`\r\n\x1b[90m${root.dataset.disconnectedLabel || 'Session closed'}\x1b[0m`);
                }
            });
            socket.addEventListener('error', () => {
                if (connecting) showError(root.dataset.authenticationError || 'Authentication failed');
            });
        } catch {
            credentials.password = '';
            connected = false;
            connecting = false;
            setState('disconnected', root.dataset.disconnectedLabel || 'Session closed');
            resetButtons();
            showError(root.dataset.authenticationError || 'Authentication failed');
        } finally {
            submitButton.disabled = false;
        }
    });

    modalElement?.addEventListener('hidden.bs.modal', () => {
        passwordInput.value = '';
        if (connecting && !connected) closeSession();
    });

    window.addEventListener('pagehide', () => closeSession());
    window.addEventListener('beforeunload', () => closeSession());
}
