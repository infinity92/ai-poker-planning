<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Poker Planning</title>
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <style>
        :root {
            --table-color: #0a6c4a;
            --felt-color: #0c8259;
            --border-color: #dee2e6;
            --card-bg: #fff;
            --card-back-bg: #5d5dff;
        }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; background-color: #f0f2f5; color: #333; overflow: hidden; }
        #app { width: 100vw; height: 100vh; display: flex; justify-content: center; align-items: center; }

        /* --- Layout --- */
        .planning-board { display: grid; grid-template-columns: 250px 1fr 300px; grid-template-rows: 60px 1fr; width: 100%; height: 100%; background-color: #f8f9fa; }
        .participants-panel { grid-column: 1 / 2; grid-row: 1 / 3; background: #fff; padding: 15px; border-right: 1px solid var(--border-color); overflow-y: auto; }
        .tasks-panel { grid-column: 3 / 4; grid-row: 1 / 3; background: #fff; padding: 15px; border-left: 1px solid var(--border-color); overflow-y: auto; }
        .top-bar {
            grid-column: 2 / 3;
            grid-row: 1 / 2;
            display: flex;
            justify-content: space-between; /* Change to space-between */
            align-items: center;
            padding: 0 20px;
            border-bottom: 1px solid var(--border-color);
        }
        .main-panel { grid-column: 2 / 3; grid-row: 2 / 3; display: flex; flex-direction: column; justify-content: space-between; align-items: center; padding: 20px; overflow-y: auto; }

        /* --- Components --- */
        h2 { margin-top: 0; font-size: 1.2em; color: #495057; padding-bottom: 10px; border-bottom: 1px solid var(--border-color); }
        .participants-list, .tasks-list { list-style: none; padding: 0; }
        .participant-item { padding: 8px; margin-bottom: 5px; border-radius: 4px; }
        .participant-item.voted { background-color: #d4edda; font-weight: bold; }

        .tasks-list { display: flex; flex-direction: column; gap: 10px; margin-top: 15px; }
        .task-item {
            background-color: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 15px;
            display: flex;
            flex-direction: column; /* Arrange items vertically */
            align-items: stretch; /* Stretch items to full width */
            gap: 10px; /* Space between title and actions */
            transition: all 0.2s ease-in-out;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .task-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .task-item .task-title { font-weight: 600; color: #343a40; font-size: 1.1em; }
        .task-item .task-actions {
            display: flex;
            justify-content: space-between; /* Pushes scores and buttons apart */
            align-items: center;
            width: 100%;
        }
        .task-item .scores { font-size: 12px; font-weight: 600; color: #007bff; background-color: #e7f5ff; padding: 4px 8px; border-radius: 4px; }
        .task-item.voting { border-left: 4px solid #007bff; }
        .task-item.completed .task-title { text-decoration: none; color: #6c757d; }
        .task-item.completed .scores { background-color: #e2e3e5; color: #495057; }

        .current-task-title { font-size: 1.5em; font-weight: 600; }

        .table {
            width: 80%;
            min-height: 150px;
            background: var(--table-color);
            border: 10px solid #8b4513;
            border-radius: 100px / 60px;
            display: flex; justify-content: center; align-items: center;
            text-align: center; color: white; font-size: 1.8em; font-weight: bold;
            box-shadow: 0 10px 20px rgba(0,0,0,0.3), inset 0 0 15px rgba(0,0,0,0.4);
            margin-bottom: 40px;
        }
        .table-content button { background-color: #ffc107; color: #333; padding: 15px 30px; font-size: 1em; border: none; border-radius: 8px; cursor: pointer; }

        .player-cards-area { display: flex; justify-content: center; align-items: flex-start; gap: 20px; min-height: 180px; width: 100%; flex-wrap: wrap; }
        .card-container { perspective: 1000px; text-align: center; }
        .card {
            width: 90px;
            height: 135px;
            position: relative;
            transition: transform 0.8s;
            transform-style: preserve-3d;
            margin: 0 auto;
        }
        /*.card.is-flipped { transform: rotateY(180deg); }*/
        .card-face {
            position: absolute;
            width: 100%;
            height: 100%;
            /*backface-visibility: hidden;*/
            border-radius: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 2.5em;
            font-weight: bold;
            box-shadow: 0 5px 15px rgba(0,0,0,0.25);
        }
        .card-front {
            background: #fdfdfd;
            border: 1px solid #e0e0e0;
        }
        .card-back {
            background-color: #4a00e0;
            background-image: linear-gradient(160deg, #8e2de2 0%, #4a00e0 100%);
            border: 2px solid #fff;
            box-sizing: border-box;
            transform: rotateY(180deg);
        }

        .participant-name-tag { margin-top: 8px; font-weight: 500; font-size: 14px; }

        .my-hand { display: flex; justify-content: center; gap: 10px; padding: 20px; background: rgba(0,0,0,0.05); border-radius: 10px; align-self: stretch; margin-top: auto; }
        .vote-card { width: 60px; height: 90px; border: 2px solid #007bff; border-radius: 8px; display: flex; justify-content: center; align-items: center; font-size: 1.8em; font-weight: bold; cursor: pointer; transition: all 0.2s ease-in-out; background: var(--card-bg); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .vote-card:hover { transform: translateY(-5px) scale(1.05); box-shadow: 0 8px 15px rgba(0,0,0,0.2); }
        .vote-card.selected { background-color: #007bff; color: white; border-color: #004494; transform: translateY(-5px) scale(1.05); box-shadow: 0 0 10px rgba(0, 123, 255, 0.7); }

        /* Modal for initial forms */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
        }
        input[type="text"] {
            padding: 10px;
            font-size: 16px;
            border-radius: 4px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
            width: 100%;
            box-sizing: border-box;
        }
        button, .action-btn {
            padding: 10px 15px;
            font-size: 14px;
            border-radius: 4px;
            border: none;
            background-color: #007bff;
            color: white;
            cursor: pointer;
        }
        .action-btn {
            background-color: #6c757d;
            font-size: 12px;
            padding: 5px 10px;
        }
        .revote-btn {
            background-color: #17a2b8;
        }
        .reveal-btn {
            background: linear-gradient(45deg, #ffc107, #f7971e);
            padding: 15px 40px;
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border: 2px solid white;
            border-radius: 50px;
            color: #333;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
        }
        button:hover, .action-btn:hover {
            opacity: 0.9;
        }
        .reveal-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
        }
        .header-actions button {
            margin-left: 10px;
            background-color: #6c757d;
        }
        .add-task-row {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .add-task-row input {
            flex: 1;
            padding: 10px;
            font-size: 14px;
            border-radius: 4px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            margin-bottom: 0; /* Remove margin to align with button */
        }
        .add-task-row button {
            flex-shrink: 0;
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 4px;
            border: 1px solid transparent; /* Match input border */
            background-color: #007bff;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
            box-sizing: border-box; /* Ensure consistent height calculation */
        }
        .add-task-row button:hover {
            background-color: #0056b3;
        }
        .card-value {
            font-size: 1.2em;
            font-weight: bold;
        }
        .card-back-icon {
            font-size: 2em;
            color: rgba(255, 255, 255, 0.7);
        }
    </style>
</head>
<body>
<div id="app"></div>

<script>
    // --- STATE AND SETUP (mostly unchanged) ---
    const app = document.getElementById('app');
    const params = new URLSearchParams(window.location.search);
    const roomId = params.get('room');
    let state = { room: null, participants: [], tasks: [], currentUser: { id: null, name: null } };
    let ws;
    const fibonacci = [0, 1, 2, 3, 5, 8, 13, 21, 34, 55, 89, '?'];

    async function init() {
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
            // При любом обновлении просто перезагружаем состояние
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
            // Fallback for insecure contexts
            const textArea = document.createElement("textarea");
            textArea.value = window.location.href;
            textArea.style.position = "fixed";  // Prevent scrolling to bottom of page in MS Edge.
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

    // --- COMPLETELY REWRITTEN RENDER FUNCTION ---
    function renderRoomUI() {
        const isHost = state.room.created_by === state.currentUser.id;

        // ИСПРАВЛЕННАЯ ЛОГИКА: Сначала ищем задачу на голосовании, и только потом - завершенные.
        let currentTask = state.tasks.find(t => t.status === 'voting');
        if (!currentTask) {
            const completedTasks = state.tasks.filter(t => t.status === 'completed');
            if (completedTasks.length > 0) {
                currentTask = completedTasks[completedTasks.length - 1]; // Показываем последнюю завершенную
            }
        }

        const isVotingPhase = currentTask && currentTask.status === 'voting';
        const cardsRevealed = currentTask && currentTask.status === 'completed';

        // 1. Left Panel: Participants
        const participantsHTML = state.participants.map(p => {
            const hasVoted = currentTask && currentTask.votes.find(v => v.participant_id === p.id);
            return `<li class="participant-item ${hasVoted ? 'voted' : ''}">${p.name} ${p.id === state.room.created_by ? '👑' : ''}</li>`;
        }).join('');

        // 2. Right Panel: Tasks
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

        // 3. Top Bar: Current Task Title
        const topBarHTML = `
            <h1 class="current-task-title">${currentTask ? currentTask.title : "Poker Planning"}</h1>
            <div class="header-actions">
                <button id="copyLinkBtn" class="action-btn">Copy Room Link</button>
                <button id="exitRoomBtn" class="action-btn">Exit Room</button>
            </div>
        `;

        // 4. Main Panel: Table and Participants Area
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

            // Класс для открытой/закрытой карты
            const cardClass = cardsRevealed ? 'card is-flipped open-card' : 'card closed-card';
            // Содержимое лицевой стороны (только если открыто)
            const frontContent = cardsRevealed ? `<span class="card-value">${vote.score}</span>` : '';
            // Содержимое рубашки (можно добавить иконку или узор)
            const backContent = `<span class="card-back-icon">&#127136;</span>`; // Юникод игральной карты
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

        // 5. Bottom Panel: My Hand for voting
        let myHandHTML = '';
        if (isVotingPhase) {
            const myVote = currentTask.votes.find(v => v.participant_id === state.currentUser.id);
            myHandHTML = fibonacci.map(num =>
                `<div class="vote-card ${myVote && myVote.score == num ? 'selected' : ''}" data-score="${num}">${num}</div>`
            ).join('');
            myHandHTML = `<div class="my-hand">${myHandHTML}</div>`;
        }

        // --- Final Assembly ---
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

        // --- EVENT LISTENERS ---
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


    // --- ACTION HANDLERS (UNCHANGED OR SLIGHTLY MODIFIED) ---
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
            // UI обновится через WebSocket
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
            // UI обновится через WebSocket
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
            // UI обновится через WebSocket
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
            // UI will update via WebSocket
        } catch (error) {
            console.error('Error starting re-vote:', error);
            alert('Failed to start re-vote.');
        }
    }

    init();
</script>
</body>
</html>
