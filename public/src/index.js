import '../styles.css';

let app;
const params = new URLSearchParams(window.location.search);
const roomId = params.get('room');
let state = { room: null, participants: [], tasks: [], currentUser: { id: null, name: null } };
let ws;
const fibonacci = [0, 1, 2, 3, 5, 8, 13, 21, 34, 55, 89, '?'];

async function init() {
    app = document.getElementById('app');
    if (roomId) {
        await loadRoomState();
        setupWebSocket();
    } else {
        renderCreateRoomUI();
    }
}

async function loadRoomState(isUpdate = false) {
    try {
        const response = await fetch(`/api/get_room_state.php?room_id=${roomId}`);
        if (!response.ok) {
            if(response.status === 404) app.innerHTML = `<h1>Room not found</h1>`;
            else throw new Error(`Server error: ${response.statusText}`);
            return;
        }
        const data = await response.json();
        state.room = data.room;
        state.participants = data.participants;
        state.tasks = data.tasks;

        if (isUpdate) {
            renderRoomUI();
            return;
        }

        const storedParticipant = JSON.parse(localStorage.getItem(`poker_room_${roomId}`));
        if (storedParticipant && state.participants.find(p => p.id === storedParticipant.id)) {
            state.currentUser = storedParticipant;
            renderRoomUI();
        } else {
            renderJoinModal();
        }
    } catch (error) {
        console.error('Failed to load room state:', error);
        app.innerHTML = '<h1>Error loading room</h1>';
    }
}

function setupWebSocket() {
    ws = new WebSocket(`ws://${window.location.hostname}:8181`);

    ws.onopen = () => {
        console.log('WebSocket connected');
        ws.send(JSON.stringify({ type: 'subscribe', roomId: roomId }));
    };

    ws.onmessage = (event) => {
        const message = JSON.parse(event.data);
        console.log('WS Message:', message.type);
        loadRoomState(true);
    };

    ws.onclose = () => {
        console.log('WebSocket disconnected. Attempting to reconnect...');
        setTimeout(setupWebSocket, 3000);
    };

    ws.onerror = (err) => {
        console.error('WebSocket error:', err);
    };
}

function renderCreateRoomUI() {
    app.innerHTML = `
        <div class="container">
            <h1>Poker Planning</h1>
            <p>Enter your name to start a new planning session.</p>
            <input type="text" id="nameInput" placeholder="Your Name" required>
            <button id="createRoomBtn">Create Room</button>
        </div>
    `;
    document.getElementById('createRoomBtn').onclick = handleCreateRoom;
}

function renderJoinModal() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content">
            <h2>Join Planning Room</h2>
            <p>Enter your name to join.</p>
            <input type="text" id="joinNameInput" placeholder="Your Name" required>
            <button id="joinRoomBtn">Join</button>
        </div>
    `;
    app.appendChild(modal);
    document.getElementById('joinRoomBtn').onclick = handleJoinRoom;
}

function handleCopyLink() {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Room link copied to clipboard!');
        }, (err) => {
            console.error('Could not copy text: ', err);
            alert('Failed to copy link.');
        });
    } else {
        const textArea = document.createElement("textarea");
        textArea.value = window.location.href;
        textArea.style.position = "fixed";
        textArea.style.left = "-9999px";
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            alert('Room link copied to clipboard!');
        } catch (err) {
            console.error('Fallback: Oops, unable to copy', err);
            alert('Failed to copy link.');
        }
        document.body.removeChild(textArea);
    }
}

function handleExitRoom() {
    if (confirm('Are you sure you want to exit the room?')) {
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({
                type: 'leave',
                roomId: roomId,
                participantId: state.currentUser.id
            }));
        }
        localStorage.removeItem(`poker_room_${roomId}`);
        window.location.href = window.location.pathname.split('/').slice(0, -1).join('/') + '/';
    }
}

function renderRoomUI() {
    const isHost = state.room.created_by === state.currentUser.id;
    let currentTask = state.tasks.find(t => t.status === 'voting');
    if (!currentTask) {
        const completedTasks = state.tasks.filter(t => t.status === 'completed');
        if (completedTasks.length > 0) {
            currentTask = completedTasks[completedTasks.length - 1];
        }
    }
    const isVotingPhase = currentTask && currentTask.status === 'voting';
    const cardsRevealed = currentTask && currentTask.status === 'completed';
    const participantsHTML = state.participants.map(p => {
        const hasVoted = currentTask && currentTask.votes.find(v => v.participant_id === p.id);
        return `<li class="participant-item ${hasVoted ? 'voted' : ''}">${p.name} ${p.id === state.room.created_by ? '👑' : ''}</li>`;
    }).join('');
    const tasksHTML = state.tasks.map(t => {
        let buttonsHTML = '';
        if (isHost) {
            if (t.status === 'pending') {
                buttonsHTML = `<button class="action-btn start-vote-btn" data-task-id="${t.id}">Start Vote</button>`;
            } else if (t.status === 'completed') {
                buttonsHTML = `<button class="action-btn revote-btn" data-task-id="${t.id}">Re-vote</button>`;
            }
        }
        const scores = t.status === 'completed' ?
            `<span class="scores">Avg: ${t.final_score}, Median: ${t.median_score}</span>` : '';
        return `<li class="task-item ${t.status}">
                    <div class="task-title">${t.title}</div>
                    <div class="task-actions">
                        <div>${scores}</div>
                        <div>${buttonsHTML}</div>
                    </div>
                </li>`;
    }).join('');
    const topBarHTML = `
        <h1 class="current-task-title">${currentTask ? currentTask.title : "Poker Planning"}</h1>
        <div class="header-actions">
            <button id="copyLinkBtn" class="action-btn">Copy Room Link</button>
            <button id="exitRoomBtn" class="action-btn">Exit Room</button>
        </div>
    `;
    let tableContentHTML = '';
    if (isVotingPhase && !cardsRevealed) {
        const allVoted = state.participants.length === currentTask.votes.length;
        if (isHost && allVoted) {
            tableContentHTML = `<button id="revealCardsBtn" class="reveal-btn" data-task-id="${currentTask.id}">Reveal Cards</button>`;
        } else if (allVoted) {
            tableContentHTML = 'All participants have voted. Waiting for host to reveal cards.';
        } else {
            tableContentHTML = 'Waiting for all participants to vote...';
        }
    } else if (cardsRevealed) {
        tableContentHTML = `Final Score: ${currentTask.final_score}`;
    }
    const participantsOnTableHTML = state.participants.map(p => {
        const vote = currentTask ? currentTask.votes.find(v => v.participant_id === p.id) : null;
        if (!vote) return '';
        const cardClass = cardsRevealed ? 'card is-flipped open-card' : 'card closed-card';
        const frontContent = cardsRevealed ? `<span class="card-value">${vote.score}</span>` : '';
        const backContent = `<span class="card-back-icon">&#127136;</span>`;
        const cardBack = cardsRevealed ? 'card-front' : 'card-back';
        return `
            <div class="card-container">
                <div class="${cardClass}">
                    <div class="card-face ${cardBack}">${frontContent}</div>
                </div>
                <div class="participant-name-tag">${p.name}</div>
            </div>
        `;
    }).join('');
    let myHandHTML = '';
    if (isVotingPhase) {
        const myVote = currentTask.votes.find(v => v.participant_id === state.currentUser.id);
        myHandHTML = fibonacci.map(num =>
            `<div class="vote-card ${myVote && myVote.score == num ? 'selected' : ''}" data-score="${num}">${num}</div>`
        ).join('');
        myHandHTML = `<div class="my-hand">${myHandHTML}</div>`;
    }
    app.innerHTML = `
        <div class="planning-board">
            <div class="participants-panel">
                <h2>Participants</h2>
                <ul class="participants-list">${participantsHTML}</ul>
            </div>
            <div class="top-bar">${topBarHTML}</div>
            <div class="main-panel">
                <div class="table">
                    <div class="table-content">${tableContentHTML}</div>
                </div>
                <div class="player-cards-area">${participantsOnTableHTML}</div>
                ${myHandHTML}
            </div>
            <div class="tasks-panel">
                <h2>Tasks</h2>
                ${isHost ? `
                <div class="add-task-row">
                    <input type="text" id="newTaskInput" placeholder="New Task Title">
                    <button id="addTaskBtn">Add Task</button>
                </div>
                ` : ''}
                <ul class="tasks-list">${tasksHTML}</ul>
            </div>
        </div>
    `;
    document.querySelectorAll('.vote-card').forEach(card => card.onclick = handleVote);
    if (isHost) {
        document.getElementById('addTaskBtn')?.addEventListener('click', handleAddTask);
        document.querySelectorAll('.start-vote-btn').forEach(btn => btn.onclick = () => handleStartVote(btn.dataset.taskId));
        document.querySelectorAll('.revote-btn').forEach(btn => btn.onclick = () => handleRevote(btn.dataset.taskId));
        document.getElementById('revealCardsBtn')?.addEventListener('click', handleRevealCards);
    }
    document.getElementById('copyLinkBtn').onclick = handleCopyLink;
    document.getElementById('exitRoomBtn').onclick = handleExitRoom;
}

async function handleCreateRoom() {
    const name = document.getElementById('nameInput').value;
    if (!name) { alert('Please enter your name.'); return; }
    try {
        const response = await fetch('/api/create_room.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name })
        });
        const result = await response.json();
        if (response.ok && result.success) {
            localStorage.setItem(`poker_room_${result.roomId}`, JSON.stringify({ id: result.participantId, name: name }));
            window.location.href = result.url;
        } else {
            alert('Error creating room: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Failed to create room:', error);
        alert('An error occurred. Please check the console.');
    }
}

async function handleJoinRoom() {
    const name = document.getElementById('joinNameInput').value;
    if (!name) { alert('Please enter your name.'); return; }
    try {
        const response = await fetch('/api/join_room.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ room_id: roomId, name: name })
        });
        const result = await response.json();
        if (response.ok && result.success) {
            state.currentUser = { id: result.participantId, name: name };
            localStorage.setItem(`poker_room_${roomId}`, JSON.stringify(state.currentUser));
            document.querySelector('.modal-overlay').remove();
            await loadRoomState(true);
        } else {
            alert('Error joining room: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Failed to join room:', error);
        alert('An error occurred. Please check the console.');
    }
}

async function handleAddTask(event) {
    event.preventDefault();
    const titleInput = document.getElementById('newTaskInput');
    const title = titleInput.value;
    if (!title) { alert('Task title cannot be empty.'); return; }
    try {
        const response = await fetch('/api/add_task.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                room_id: roomId,
                participant_id: state.currentUser.id,
                title: title
            })
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Failed to add task');
        titleInput.value = '';
    } catch (error) {
        console.error('Error adding task:', error);
        alert(error.message);
    }
}

async function handleStartVote(taskId) {
    try {
        await fetch('/api/start_vote.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ room_id: roomId, participant_id: state.currentUser.id, task_id: taskId })
        });
    } catch (error) { console.error('Error starting vote:', error); }
}

async function handleVote(event) {
    const score = event.target.dataset.score;
    const currentVotingTask = state.tasks.find(t => t.status === 'voting');
    if (!currentVotingTask) return;
    try {
        await fetch('/api/vote.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                room_id: roomId,
                task_id: currentVotingTask.id,
                participant_id: state.currentUser.id,
                score: score
            })
        });
    } catch (error) { console.error('Error submitting vote:', error); }
}

async function handleRevealCards() {
    const currentVotingTask = state.tasks.find(t => t.status === 'voting');
    if (!currentVotingTask) return;
    try {
        await fetch('/api/reveal_cards.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ room_id: roomId, participant_id: state.currentUser.id, task_id: currentVotingTask.id })
        });
    } catch (error) { console.error('Error revealing cards:', error); }
}

async function handleRevote(taskId) {
    if (!confirm('Are you sure you want to re-vote on this task? All previous votes will be cleared.')) {
        return;
    }
    try {
        await fetch('/api/revote_task.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                room_id: roomId,
                participant_id: state.currentUser.id,
                task_id: taskId
            })
        });
    } catch (error) {
        console.error('Error starting re-vote:', error);
        alert('Failed to start re-vote.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    init();
});
