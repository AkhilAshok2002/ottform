// Video Player JavaScript

class VideoPlayer {
    constructor(videoElement) {
        this.video = videoElement;
        this.container = videoElement.parentElement;
        this.controls = null;
        this.isPlaying = false;
        this.isMuted = false;
        this.volume = 1;
        
        this.init();
    }
    
    init() {
        // Create custom controls
        this.createControls();
        
        // Add event listeners
        this.video.addEventListener('timeupdate', () => this.updateProgress());
        this.video.addEventListener('volumechange', () => this.updateVolume());
        this.video.addEventListener('play', () => this.updatePlayButton(true));
        this.video.addEventListener('pause', () => this.updatePlayButton(false));
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => this.handleKeyboard(e));
    }
    
    createControls() {
        this.controls = document.createElement('div');
        this.controls.className = 'video-controls';
        
        this.controls.innerHTML = `
            <div class="progress-bar">
                <div class="progress"></div>
                <div class="buffer"></div>
            </div>
            <div class="controls-buttons">
                <button class="play-pause"><i class="fas fa-play"></i></button>
                <button class="volume">
                    <i class="fas fa-volume-up"></i>
                    <input type="range" min="0" max="1" step="0.1" value="1">
                </button>
                <span class="time">00:00 / 00:00</span>
                <button class="fullscreen"><i class="fas fa-expand"></i></button>
            </div>
        `;
        
        this.container.appendChild(this.controls);
        this.attachControlEvents();
    }
    
    attachControlEvents() {
        const playBtn = this.controls.querySelector('.play-pause');
        const volumeBtn = this.controls.querySelector('.volume');
        const volumeSlider = this.controls.querySelector('.volume input');
        const progressBar = this.controls.querySelector('.progress-bar');
        const fullscreenBtn = this.controls.querySelector('.fullscreen');
        
        playBtn.addEventListener('click', () => this.togglePlay());
        volumeBtn.addEventListener('click', () => this.toggleMute());
        volumeSlider.addEventListener('input', (e) => this.setVolume(e.target.value));
        progressBar.addEventListener('click', (e) => this.seek(e));
        fullscreenBtn.addEventListener('click', () => this.toggleFullscreen());
        
        // Show/hide controls on mouse move
        let timeout;
        this.container.addEventListener('mousemove', () => {
            this.controls.classList.add('visible');
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                if (this.isPlaying) {
                    this.controls.classList.remove('visible');
                }
            }, 3000);
        });
    }
    
    togglePlay() {
        if (this.video.paused) {
            this.video.play();
        } else {
            this.video.pause();
        }
    }
    
    updatePlayButton(isPlaying) {
        const btn = this.controls.querySelector('.play-pause i');
        btn.className = isPlaying ? 'fas fa-pause' : 'fas fa-play';
        this.isPlaying = isPlaying;
    }
    
    toggleMute() {
        this.video.muted = !this.video.muted;
        this.updateVolumeIcon();
    }
    
    setVolume(value) {
        this.video.volume = value;
        this.updateVolumeIcon();
    }
    
    updateVolume() {
        const slider = this.controls.querySelector('.volume input');
        slider.value = this.video.volume;
        this.updateVolumeIcon();
    }
    
    updateVolumeIcon() {
        const icon = this.controls.querySelector('.volume i');
        if (this.video.muted || this.video.volume === 0) {
            icon.className = 'fas fa-volume-mute';
        } else if (this.video.volume < 0.5) {
            icon.className = 'fas fa-volume-down';
        } else {
            icon.className = 'fas fa-volume-up';
        }
    }
    
    updateProgress() {
        const progress = this.controls.querySelector('.progress');
        const percent = (this.video.currentTime / this.video.duration) * 100;
        progress.style.width = percent + '%';
        
        const timeSpan = this.controls.querySelector('.time');
        timeSpan.textContent = `${this.formatTime(this.video.currentTime)} / ${this.formatTime(this.video.duration)}`;
    }
    
    formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }
    
    seek(e) {
        const rect = e.currentTarget.getBoundingClientRect();
        const percent = (e.clientX - rect.left) / rect.width;
        this.video.currentTime = percent * this.video.duration;
    }
    
    toggleFullscreen() {
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            this.container.requestFullscreen();
        }
        
        const icon = this.controls.querySelector('.fullscreen i');
        icon.className = document.fullscreenElement ? 'fas fa-compress' : 'fas fa-expand';
    }
    
    handleKeyboard(e) {
        switch(e.key) {
            case ' ':
            case 'Space':
                e.preventDefault();
                this.togglePlay();
                break;
            case 'f':
                this.toggleFullscreen();
                break;
            case 'm':
                this.toggleMute();
                break;
            case 'ArrowRight':
                this.video.currentTime += 10;
                break;
            case 'ArrowLeft':
                this.video.currentTime -= 10;
                break;
            case 'ArrowUp':
                this.video.volume = Math.min(1, this.video.volume + 0.1);
                break;
            case 'ArrowDown':
                this.video.volume = Math.max(0, this.video.volume - 0.1);
                break;
        }
    }
}

// Initialize player when video exists
document.addEventListener('DOMContentLoaded', () => {
    const video = document.getElementById('videoPlayer');
    if (video) {
        new VideoPlayer(video);
    }
});