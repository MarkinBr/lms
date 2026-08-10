/**
 * Premium Classroom - Advanced Features
 * Enhanced learning experience with interactive elements
 */

class PremiumClassroom {
    constructor() {
        this.videoPlayer = null;
        this.studyTimer = null;
        this.progressTracker = null;
        this.quizManager = null;
        this.notesManager = null;
        this.init();
    }

    init() {
        this.initVideoPlayer();
        this.initStudyTimer();
        this.initProgressTracker();
        this.initQuizManager();
        this.initNotesManager();
        this.initKeyboardShortcuts();
        this.initAnimations();
    }

    // ===== VIDEO PLAYER =====
    initVideoPlayer() {
        const videoContainer = document.getElementById('video-container');
        if (!videoContainer) return;

        this.videoPlayer = {
            element: videoContainer.querySelector('iframe'),
            isPlaying: false,
            currentTime: 0,
            duration: 0,
            speed: 1.0,
            quality: 'auto',

            play() {
                this.isPlaying = true;
                this.element.contentWindow.postMessage('{"event":"command","func":"playVideo","args":""}', '*');
                this.updatePlayButton(true);
            },

            pause() {
                this.isPlaying = false;
                this.element.contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*');
                this.updatePlayButton(false);
            },

            togglePlay() {
                this.isPlaying ? this.pause() : this.play();
            },

            setSpeed(speed) {
                this.speed = speed;
                this.element.contentWindow.postMessage(`{"event":"command","func":"setPlaybackRate","args":[${speed}]}`, '*');
                document.getElementById('speed-text').textContent = speed + 'x';
            },

            nextSpeed() {
                const speeds = [0.5, 0.75, 1, 1.25, 1.5, 2];
                const currentIndex = speeds.indexOf(this.speed);
                const nextIndex = (currentIndex + 1) % speeds.length;
                this.setSpeed(speeds[nextIndex]);
            },

            seek(seconds) {
                this.element.contentWindow.postMessage(`{"event":"command","func":"seekTo","args":[${seconds}, true]}`, '*');
            },

            skip(seconds) {
                this.seek(this.currentTime + seconds);
            },

            updatePlayButton(playing) {
                const playIcon = document.getElementById('play-icon');
                if (!playIcon) return;

                if (playing) {
                    playIcon.innerHTML = '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />';
                } else {
                    playIcon.innerHTML = '<path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />';
                }
            },

            toggleFullscreen() {
                if (!document.fullscreenElement) {
                    videoContainer.requestFullscreen();
                } else {
                    document.exitFullscreen();
                }
            },

            // Picture-in-Picture mode
            togglePiP() {
                if (document.pictureInPictureElement) {
                    document.exitPictureInPicture();
                } else if (document.pictureInPictureEnabled) {
                    this.element.requestPictureInPicture();
                }
            },

            // Add timestamps/chapters
            addChapter(time, title) {
                const chapterList = document.getElementById('chapter-list') || this.createChapterList();
                const chapter = document.createElement('div');
                chapter.className = 'chapter-item flex items-center justify-between p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer';
                chapter.innerHTML = `
                    <div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">${title}</span>
                    </div>
                    <span class="text-xs text-gray-500">${this.formatTime(time)}</span>
                `;
                chapter.onclick = () => this.seek(time);
                chapterList.appendChild(chapter);
            },

            formatTime(seconds) {
                const mins = Math.floor(seconds / 60);
                const secs = Math.floor(seconds % 60);
                return `${mins}:${secs.toString().padStart(2, '0')}`;
            },

            createChapterList() {
                const container = document.createElement('div');
                container.id = 'chapter-list';
                container.className = 'mt-4 space-y-2';
                videoContainer.parentElement.appendChild(container);
                return container;
            }
        };

        // Bind video player to window for global access
        window.togglePlay = () => this.videoPlayer.togglePlay();
        window.changeSpeed = () => this.videoPlayer.nextSpeed();
        window.toggleFullscreen = () => this.videoPlayer.toggleFullscreen();
    }

    // ===== STUDY TIMER =====
    initStudyTimer() {
        this.studyTimer = {
            startTime: Date.now(),
            totalTime: 0,
            isActive: true,
            
            getElapsedTime() {
                return Math.floor((Date.now() - this.startTime) / 1000);
            },

            formatTime(seconds) {
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                const secs = seconds % 60;

                if (hours > 0) {
                    return `${hours}h ${minutes}m`;
                }
                return `${minutes}m ${secs}s`;
            },

            updateDisplay() {
                const timerDisplay = document.getElementById('study-timer');
                if (timerDisplay) {
                    timerDisplay.textContent = this.formatTime(this.getElapsedTime());
                }
            },

            pause() {
                this.isActive = false;
                this.totalTime += this.getElapsedTime();
            },

            resume() {
                this.startTime = Date.now();
                this.isActive = true;
            },

            async save(aulaId, csrfToken) {
                const totalMinutes = Math.round(this.totalTime / 60);
                
                try {
                    const response = await fetch(`/aluno/aula/${aulaId}/tempo`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ tempo: totalMinutes })
                    });
                    
                    return await response.json();
                } catch (error) {
                    console.error('Error saving study time:', error);
                    return null;
                }
            }
        };

        // Update timer display every second
        setInterval(() => {
            if (this.studyTimer.isActive) {
                this.studyTimer.updateDisplay();
            }
        }, 1000);

        // Save time on page unload
        window.addEventListener('beforeunload', () => {
            const aulaId = document.querySelector('[data-aula-id]')?.dataset.aulaId;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            
            if (aulaId && csrfToken) {
                navigator.sendBeacon(
                    `/aluno/aula/${aulaId}/tempo`,
                    JSON.stringify({
                        tempo: Math.round(this.studyTimer.getElapsedTime() / 60),
                        _token: csrfToken
                    })
                );
            }
        });

        // Pause timer when tab is not visible
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.studyTimer.pause();
            } else {
                this.studyTimer.resume();
            }
        });
    }

    // ===== PROGRESS TRACKER =====
    initProgressTracker() {
        this.progressTracker = {
            currentProgress: 0,
            targetProgress: 100,
            scrollProgress: 0,
            videoProgress: 0,
            readingProgress: 0,
            quizProgress: 0,

            async updateProgress(aulaId, progress, csrfToken) {
                try {
                    const response = await fetch(`/aluno/aula/${aulaId}/progresso`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ progresso: progress })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        this.currentProgress = progress;
                        this.updateProgressBar(progress);
                    }
                    return data;
                } catch (error) {
                    console.error('Error updating progress:', error);
                    return null;
                }
            },

            updateProgressBar(progress) {
                const progressBar = document.getElementById('barra-progresso-aula');
                const progressText = document.getElementById('progresso-texto');
                
                if (progressBar) {
                    progressBar.style.width = progress + '%';
                }
                if (progressText) {
                    progressText.textContent = progress + '%';
                }

                // Animate progress ring if exists
                const progressRing = document.querySelector('.progress-ring-circle');
                if (progressRing) {
                    const circumference = 175.93;
                    const offset = circumference - (circumference * progress / 100);
                    progressRing.style.strokeDashoffset = offset;
                }
            },

            trackScrollProgress() {
                const contentArea = document.querySelector('.prose');
                if (!contentArea) return;

                window.addEventListener('scroll', () => {
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    const scrollHeight = contentArea.scrollHeight;
                    const clientHeight = window.innerHeight;
                    
                    this.scrollProgress = Math.min(100, Math.round((scrollTop / (scrollHeight - clientHeight)) * 100));
                    
                    // Update overall progress based on scroll
                    const newProgress = Math.max(this.currentProgress, this.scrollProgress);
                    if (newProgress > this.currentProgress) {
                        this.updateProgressBar(newProgress);
                    }
                });
            },

            trackVideoProgress() {
                window.addEventListener('message', (event) => {
                    try {
                        const data = JSON.parse(event.data);
                        if (data.event === 'infoDelivery' && data.info) {
                            const currentTime = data.info.currentTime || 0;
                            const duration = data.info.duration || 1;
                            this.videoProgress = Math.round((currentTime / duration) * 100);
                            
                            // Update time display
                            const timeDisplay = document.getElementById('video-time');
                            if (timeDisplay) {
                                timeDisplay.textContent = `${this.formatTime(currentTime)} / ${this.formatTime(duration)}`;
                            }
                        }
                    } catch (e) {
                        // Ignore parsing errors
                    }
                });
            },

            calculateOverallProgress() {
                // Weighted average of different progress types
                const weights = {
                    video: 0.4,
                    reading: 0.3,
                    quiz: 0.3
                };

                return Math.round(
                    this.videoProgress * weights.video +
                    this.readingProgress * weights.reading +
                    this.quizProgress * weights.quiz
                );
            },

            formatTime(seconds) {
                const mins = Math.floor(seconds / 60);
                const secs = Math.floor(seconds % 60);
                return `${mins}:${secs.toString().padStart(2, '0')}`;
            }
        };

        // Initialize progress tracking
        this.progressTracker.trackScrollProgress();
        this.progressTracker.trackVideoProgress();
    }

    // ===== QUIZ MANAGER =====
    initQuizManager() {
        this.quizManager = {
            answers: {},
            questions: [],
            currentQuestion: 0,
            score: 0,
            attempts: {},

            init() {
                const quizQuestions = document.querySelectorAll('.quiz-question');
                quizQuestions.forEach((q, index) => {
                    this.questions.push({
                        index,
                        element: q,
                        answered: false,
                        correct: false
                    });
                });
            },

            selectOption(questionIndex, optionIndex, correctAnswer) {
                this.answers[questionIndex] = optionIndex;
                this.attempts[questionIndex] = (this.attempts[questionIndex] || 0) + 1;

                const question = this.questions[questionIndex];
                if (!question) return;

                const options = question.element.querySelectorAll('.quiz-option');
                
                // Clear previous selections
                options.forEach(option => {
                    option.classList.remove('selected', 'correct', 'incorrect');
                    option.querySelector('.option-check')?.classList.add('hidden');
                });

                // Mark selected option
                const selectedOption = options[optionIndex];
                selectedOption.classList.add('selected');
                selectedOption.querySelector('.option-check')?.classList.remove('hidden');

                question.answered = true;
            },

            submitQuiz(aulaId, csrfToken) {
                let correct = 0;
                const total = this.questions.length;

                this.questions.forEach((question, index) => {
                    const options = question.element.querySelectorAll('.quiz-option');
                    const feedback = question.element.querySelector('.quiz-feedback');
                    const correctAnswer = this.getCorrectAnswer(options);
                    const userAnswer = this.answers[index];

                    if (userAnswer === correctAnswer) {
                        correct++;
                        question.correct = true;
                        options[userAnswer].classList.add('correct');
                        this.showFeedback(feedback, true, this.attempts[index]);
                    } else {
                        options[userAnswer]?.classList.add('incorrect');
                        options[correctAnswer].classList.add('correct');
                        this.showFeedback(feedback, false, this.attempts[index]);
                    }

                    feedback?.classList.remove('hidden');
                });

                this.score = Math.round((correct / total) * 100);
                
                // Update score display
                const scoreElement = document.getElementById('quiz-score');
                if (scoreElement) {
                    scoreElement.innerHTML = this.getScoreHTML(correct, total, this.score);
                }

                // Save results
                this.saveResults(aulaId, csrfToken);

                // Show achievement if score is high
                if (this.score >= 90) {
                    this.showAchievement('quiz-master');
                } else if (this.score >= 70) {
                    this.showAchievement('good-effort');
                }

                // Update progress
                if (window.premiumClassroom?.progressTracker) {
                    window.premiumClassroom.progressTracker.quizProgress = this.score;
                }
            },

            getCorrectAnswer(options) {
                // Parse from onclick attribute
                const firstOption = options[0];
                const onclick = firstOption.getAttribute('onclick');
                const match = onclick?.match(/\d+,\s*(\d+)/);
                return match ? parseInt(match[1]) : 0;
            },

            showFeedback(feedback, isCorrect, attempts) {
                if (!feedback) return;

                const messages = {
                    correct: [
                        '✅ Perfeito! Você acertou!',
                        '🎯 Excelente! Resposta correta!',
                        '⭐ Muito bem! Continue assim!',
                        '🏆 Ótimo trabalho!'
                    ],
                    incorrect: [
                        `❌ Não foi dessa vez. ${attempts > 1 ? 'Tente novamente!' : 'A resposta correta está destacada.'}`,
                        '💡 Quase lá! Veja a resposta correta em verde.',
                        '📚 Revise o conteúdo e tente novamente!',
                        '🤔 Resposta incorreta. Aprenda com o erro!'
                    ]
                };

                const message = isCorrect
                    ? messages.correct[Math.floor(Math.random() * messages.correct.length)]
                    : messages.incorrect[Math.floor(Math.random() * messages.incorrect.length)];

                feedback.className = `quiz-feedback ml-11 mt-4 p-4 rounded-lg ${
                    isCorrect
                        ? 'bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300'
                        : 'bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-300'
                }`;
                feedback.innerHTML = message;
            },

            getScoreHTML(correct, total, percentage) {
                const emoji = percentage >= 90 ? '🏆' : percentage >= 70 ? '⭐' : percentage >= 50 ? '👍' : '📚';
                return `
                    <div class="flex items-center space-x-4">
                        <span class="text-4xl">${emoji}</span>
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Sua pontuação</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                ${correct}/${total} <span class="text-blue-600">(${percentage}%)</span>
                            </div>
                        </div>
                    </div>
                `;
            },

            async saveResults(aulaId, csrfToken) {
                try {
                    const response = await fetch(`/aluno/aula/${aulaId}/quiz`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            score: this.score,
                            answers: this.answers,
                            attempts: this.attempts
                        })
                    });
                    
                    return await response.json();
                } catch (error) {
                    console.error('Error saving quiz results:', error);
                    return null;
                }
            },

            showAchievement(type) {
                const achievements = {
                    'quiz-master': {
                        icon: '🏆',
                        title: 'Mestre do Quiz!',
                        description: 'Pontuação perfeita!'
                    },
                    'good-effort': {
                        icon: '⭐',
                        title: 'Bom trabalho!',
                        description: 'Continue assim!'
                    }
                };

                const achievement = achievements[type];
                if (!achievement) return;

                // Create achievement notification
                const notification = document.createElement('div');
                notification.className = 'fixed top-20 right-4 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 border-2 border-yellow-400 z-50 animate-bounce';
                notification.innerHTML = `
                    <div class="text-center">
                        <div class="text-5xl mb-2">${achievement.icon}</div>
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-1">${achievement.title}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">${achievement.description}</p>
                    </div>
                `;

                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.classList.add('opacity-0', 'transition-opacity');
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }
        };

        // Initialize quiz manager
        this.quizManager.init();

        // Bind quiz functions to window
        window.selectOption = (q, o, c) => this.quizManager.selectOption(q, o, c);
        window.submitQuiz = () => {
            const aulaId = document.querySelector('[data-aula-id]')?.dataset.aulaId;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            this.quizManager.submitQuiz(aulaId, csrfToken);
        };
    }

    // ===== NOTES MANAGER =====
    initNotesManager() {
        const notesEditor = document.getElementById('notes-editor');
        if (!notesEditor) return;

        this.notesManager = {
            editor: notesEditor,
            autoSaveTimeout: null,
            lastSaved: null,

            init() {
                this.updateCharCount();
                this.setupAutoSave();
                this.enableMarkdown();
                this.addFormatting();
            },

            updateCharCount() {
                const charCount = document.getElementById('notes-char-count');
                if (charCount) {
                    this.editor.addEventListener('input', () => {
                        charCount.textContent = `${this.editor.value.length} caracteres`;
                    });
                }
            },

            setupAutoSave() {
                this.editor.addEventListener('input', () => {
                    clearTimeout(this.autoSaveTimeout);
                    this.autoSaveTimeout = setTimeout(() => this.save(), 2000);
                });
            },

            async save() {
                const aulaId = document.querySelector('[data-aula-id]')?.dataset.aulaId;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                
                if (!aulaId || !csrfToken) return;

                try {
                    const response = await fetch(`/aluno/aula/${aulaId}/notas`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ notas: this.editor.value })
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.lastSaved = new Date();
                        this.updateTimestamp();
                        this.showSaveIndicator();
                    }
                } catch (error) {
                    console.error('Error saving notes:', error);
                }
            },

            updateTimestamp() {
                const timestamp = document.getElementById('notes-timestamp');
                if (timestamp) {
                    timestamp.textContent = 'Última atualização: agora mesmo';
                }
            },

            showSaveIndicator() {
                const indicator = document.createElement('div');
                indicator.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
                indicator.textContent = '✓ Anotações salvas';
                document.body.appendChild(indicator);

                setTimeout(() => {
                    indicator.classList.add('opacity-0', 'transition-opacity');
                    setTimeout(() => indicator.remove(), 300);
                }, 2000);
            },

            enableMarkdown() {
                // Add markdown shortcuts
                this.editor.addEventListener('keydown', (e) => {
                    if (e.ctrlKey || e.metaKey) {
                        switch(e.key) {
                            case 'b':
                                e.preventDefault();
                                this.wrapSelection('**', '**');
                                break;
                            case 'i':
                                e.preventDefault();
                                this.wrapSelection('*', '*');
                                break;
                            case 's':
                                e.preventDefault();
                                this.save();
                                break;
                        }
                    }
                });
            },

            wrapSelection(before, after) {
                const start = this.editor.selectionStart;
                const end = this.editor.selectionEnd;
                const text = this.editor.value;
                const selection = text.substring(start, end);
                
                this.editor.value = text.substring(0, start) + before + selection + after + text.substring(end);
                this.editor.selectionStart = start + before.length;
                this.editor.selectionEnd = end + before.length;
                this.editor.focus();
            },

            addFormatting() {
                // Could add a toolbar here for rich text editing
            }
        };

        this.notesManager.init();
        window.saveNotes = () => this.notesManager.save();
    }

    // ===== KEYBOARD SHORTCUTS =====
    initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Skip if user is typing in input/textarea
            if (['INPUT', 'TEXTAREA'].includes(e.target.tagName)) return;

            switch(e.key.toLowerCase()) {
                case ' ':
                    if (this.videoPlayer) {
                        e.preventDefault();
                        this.videoPlayer.togglePlay();
                    }
                    break;
                case 'arrowright':
                    if (this.videoPlayer) {
                        e.preventDefault();
                        this.videoPlayer.skip(10);
                    }
                    break;
                case 'arrowleft':
                    if (this.videoPlayer) {
                        e.preventDefault();
                        this.videoPlayer.skip(-10);
                    }
                    break;
                case 'f':
                    if (this.videoPlayer) {
                        e.preventDefault();
                        this.videoPlayer.toggleFullscreen();
                    }
                    break;
                case 'p':
                    if (this.videoPlayer && e.altKey) {
                        e.preventDefault();
                        this.videoPlayer.togglePiP();
                    }
                    break;
            }
        });
    }

    // ===== ANIMATIONS =====
    initAnimations() {
        // Intersection Observer for fade-in animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, {
            threshold: 0.1
        });

        document.querySelectorAll('.interactive-card').forEach(card => {
            observer.observe(card);
        });

        // Add hover effects
        document.querySelectorAll('.interactive-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-4px)';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.premiumClassroom = new PremiumClassroom();
    console.log('Premium Classroom initialized!');
});

// Export for use in other scripts
export default PremiumClassroom;
