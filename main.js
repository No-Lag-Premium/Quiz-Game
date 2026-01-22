// --- QUESTIONS DATABASE ---
const questions = {
  easy: [
    { question: "1+1", answers: [{ text: "11", correct: false }, { text: "2", correct: true }, { text: "111", correct: false }, { text: "1111", correct: false }] },
    { question: "2+3", answers: [{ text: "5", correct: true }, { text: "23", correct: false }, { text: "6", correct: false }, { text: "7", correct: false }] },
    { question: "5-2", answers: [{ text: "3", correct: true }, { text: "2", correct: false }, { text: "7", correct: false }, { text: "1", correct: false }] },
    { question: "3+4", answers: [{ text: "7", correct: true }, { text: "12", correct: false }, { text: "34", correct: false }, { text: "8", correct: false }] },
    { question: "6-1", answers: [{ text: "5", correct: true }, { text: "6", correct: false }, { text: "1", correct: false }, { text: "7", correct: false }] },
    { question: "2+6", answers: [{ text: "8", correct: true }, { text: "26", correct: false }, { text: "9", correct: false }, { text: "7", correct: false }] },
    { question: "4+3", answers: [{ text: "7", correct: true }, { text: "43", correct: false }, { text: "8", correct: false }, { text: "9", correct: false }] },
    { question: "7-5", answers: [{ text: "2", correct: true }, { text: "3", correct: false }, { text: "12", correct: false }, { text: "13", correct: false }] },
    { question: "3+2", answers: [{ text: "5", correct: true }, { text: "32", correct: false }, { text: "4", correct: false }, { text: "6", correct: false }] },
    { question: "5-3", answers: [{ text: "2", correct: true }, { text: "3", correct: false }, { text: "53", correct: false }, { text: "1", correct: false }] },
  ],
  medium: [
    { question: "12+15", answers: [{ text: "27", correct: true }, { text: "1215", correct: false }, { text: "26", correct: false }, { text: "28", correct: false }] },
    { question: "20-7", answers: [{ text: "13", correct: true }, { text: "14", correct: false }, { text: "27", correct: false }, { text: "7", correct: false }] },
    { question: "5*3", answers: [{ text: "15", correct: true }, { text: "53", correct: false }, { text: "8", correct: false }, { text: "18", correct: false }] },
    { question: "18/3", answers: [{ text: "6", correct: true }, { text: "9", correct: false }, { text: "8", correct: false }, { text: "5", correct: false }] },
    { question: "7+14", answers: [{ text: "21", correct: true }, { text: "714", correct: false }, { text: "20", correct: false }, { text: "22", correct: false }] },
    { question: "16-9", answers: [{ text: "7", correct: true }, { text: "6", correct: false }, { text: "19", correct: false }, { text: "8", correct: false }] },
    { question: "3*4", answers: [{ text: "12", correct: true }, { text: "7", correct: false }, { text: "34", correct: false }, { text: "11", correct: false }] },
    { question: "15/5", answers: [{ text: "3", correct: true }, { text: "5", correct: false }, { text: "2", correct: false }, { text: "4", correct: false }] },
    { question: "9+6", answers: [{ text: "15", correct: true }, { text: "96", correct: false }, { text: "14", correct: false }, { text: "16", correct: false }] },
    { question: "12-5", answers: [{ text: "7", correct: true }, { text: "6", correct: false }, { text: "17", correct: false }, { text: "8", correct: false }] },
  ],
  hard: [
    { question: "25*4", answers: [{ text: "100", correct: true }, { text: "29", correct: false }, { text: "99", correct: false }, { text: "105", correct: false }] },
    { question: "144/12", answers: [{ text: "12", correct: true }, { text: "11", correct: false }, { text: "14", correct: false }, { text: "10", correct: false }] },
    { question: "18*3", answers: [{ text: "54", correct: true }, { text: "21", correct: false }, { text: "53", correct: false }, { text: "56", correct: false }] },
    { question: "120/5", answers: [{ text: "24", correct: true }, { text: "25", correct: false }, { text: "26", correct: false }, { text: "20", correct: false }] },
    { question: "33+44", answers: [{ text: "77", correct: true }, { text: "3344", correct: false }, { text: "78", correct: false }, { text: "76", correct: false }] },
    { question: "65-27", answers: [{ text: "38", correct: true }, { text: "37", correct: false }, { text: "35", correct: false }, { text: "39", correct: false }] },
    { question: "7*8", answers: [{ text: "56", correct: true }, { text: "58", correct: false }, { text: "54", correct: false }, { text: "55", correct: false }] },
    { question: "81/9", answers: [{ text: "9", correct: true }, { text: "8", correct: false }, { text: "10", correct: false }, { text: "11", correct: false }] },
    { question: "56+29", answers: [{ text: "85", correct: true }, { text: "57", correct: false }, { text: "86", correct: false }, { text: "84", correct: false }] },
    { question: "90-45", answers: [{ text: "45", correct: true }, { text: "44", correct: false }, { text: "46", correct: false }, { text: "40", correct: false }] },
  ]
};

// --- DOM ELEMENTS ---
const homeScreen = document.getElementById("home-screen");
const usernameScreen = document.getElementById("username-screen");
const difficultyScreen = document.getElementById("difficulty-screen");
const quizScreen = document.getElementById("quiz-screen");
const resultScreen = document.getElementById("result-screen");

const leaderboardEl = document.getElementById("leaderboard");
const playBtn = document.getElementById("play-btn");
const usernameInput = document.getElementById("username-input");
const usernameBtn = document.getElementById("username-btn");
const difficultyBtns = document.querySelectorAll(".difficulty");

const questionElement = document.getElementById("question");
const answerButtons = document.getElementById("answer-buttons");
const nextButton = document.getElementById("next-btn");
const timerEl = document.getElementById("timer");

const resultScore = document.getElementById("result-score");
const resultTime = document.getElementById("result-time");
const resultHomeBtn = document.getElementById("result-home");
const resultPlayAgainBtn = document.getElementById("result-play-again");

// --- GLOBAL VARIABLES ---
let currentUsername = "";
let currentDifficulty = "easy";
let currentQuestions = [];
let currentQuestionIndex = 0;
let score = 0;
let totalTime = 0;
let timer;
let timeLeft = 0;

// --- SHOW SCREEN ---
function showScreen(screen) {
  [homeScreen, usernameScreen, difficultyScreen, quizScreen, resultScreen].forEach(s => s.classList.remove("active"));
  screen.classList.add("active");
}

// --- LEADERBOARD ---
function loadLeaderboard() {
  let leaderboard = JSON.parse(localStorage.getItem("leaderboard") || "{}");
  leaderboardEl.innerHTML = "";
  let entries = Object.entries(leaderboard)
    .sort((a, b) => b[1].score - a[1].score || a[1].time - b[1].time)
    .slice(0, 3);
  entries.forEach(([name, data]) => {
    let li = document.createElement("li");
    li.textContent = `${name.toUpperCase()} - ${data.score} pts`;
    leaderboardEl.appendChild(li);
  });
}

// --- PLAY BUTTON ---
playBtn.addEventListener("click", () => {
  if (currentUsername) {
    showScreen(difficultyScreen);
  } else {
    usernameInput.value = "";
    showScreen(usernameScreen);
  }
});

// --- USERNAME BUTTON ---
usernameBtn.addEventListener("click", () => {
  let name = usernameInput.value.trim();
  if (name) {
    currentUsername = name.toUpperCase();
    showScreen(difficultyScreen);
  }
});

// --- ENTER KEY for USERNAME ---
usernameInput.addEventListener("keypress", function(e) {
  if (e.key === "Enter") {
    usernameBtn.click();
  }
});

// --- DIFFICULTY BUTTONS ---
difficultyBtns.forEach(btn => {
  btn.addEventListener("click", () => {
    currentDifficulty = btn.dataset.level;
    currentQuestions = [...questions[currentDifficulty]];
    shuffleArray(currentQuestions);
    currentQuestionIndex = 0;
    score = 0;
    totalTime = 0;
    showScreen(quizScreen);
    showQuestion();
  });
});

// --- QUIZ LOGIC ---
function showQuestion() {
  resetState();
  const currentQ = currentQuestions[currentQuestionIndex];
  questionElement.textContent = currentQ.question;
  
  currentQ.answers.forEach(ans => {
    const btn = document.createElement("button");
    btn.textContent = ans.text;
    btn.classList.add("btn");
    btn.dataset.correct = ans.correct;
    btn.addEventListener("click", selectAnswer);
    answerButtons.appendChild(btn);
  });
  
  timeLeft = currentDifficulty == "easy" ? 10 : currentDifficulty == "medium" ? 12 : 15;
  timerEl.textContent = `Time Left: ${timeLeft}s`;
  clearInterval(timer);
  timer = setInterval(() => {
    timeLeft--;
    totalTime++;
    timerEl.textContent = `Time Left: ${timeLeft}s`;
    if (timeLeft <= 0) {
      clearInterval(timer);
      selectAnswer(null, true);
    }
  }, 1000);
}

function resetState() {
  nextButton.style.display = "none";
  answerButtons.innerHTML = "";
}

// --- SELECT ANSWER ---
function selectAnswer(e, timeout = false) {
  clearInterval(timer);
  const selectedBtn = e ? e.target : null;
  
  if (selectedBtn) {
    const isCorrect = selectedBtn.dataset.correct === "true";
    if (isCorrect) score++;
    if (isCorrect) {
      selectedBtn.classList.add("correct");
    } else {
      selectedBtn.classList.add("incorrect");
      // reveal correct button immediately
      Array.from(answerButtons.children).forEach(btn => {
        if (btn.dataset.correct === "true") btn.classList.add("correct");
      });
    }
    Array.from(answerButtons.children).forEach(btn => btn.disabled = true);
  } else if (timeout) { // time ran out
    Array.from(answerButtons.children).forEach(btn => {
      if (btn.dataset.correct === "true") btn.classList.add("correct");
      btn.disabled = true;
    });
  }
  nextButton.style.display = "block";
}

// --- NEXT BUTTON ---
nextButton.addEventListener("click", () => {
  currentQuestionIndex++;
  if (currentQuestionIndex < currentQuestions.length) {
    showQuestion();
  } else {
    showResult();
  }
});

// --- RESULT SCREEN ---
function showResult() {
  resultScore.textContent = `${score} / ${currentQuestions.length}`;
  resultTime.textContent = `${totalTime} s`;
  
  let leaderboard = JSON.parse(localStorage.getItem("leaderboard") || "{}");
  if (!leaderboard[currentUsername] || score > leaderboard[currentUsername].score || (score == leaderboard[currentUsername].score && totalTime < leaderboard[currentUsername].time)) {
    leaderboard[currentUsername] = { score, totalTime };
    localStorage.setItem("leaderboard", JSON.stringify(leaderboard));
  }
  loadLeaderboard();
  
  showScreen(resultScreen);
}

// --- RESULT BUTTONS ---
resultHomeBtn.addEventListener("click", () => showScreen(homeScreen));

resultPlayAgainBtn.addEventListener("click", () => {
  currentQuestionIndex = 0;
  score = 0;
  totalTime = 0;
  showScreen(difficultyScreen);
});

// --- UTILITIES ---
function shuffleArray(array) {
  for (let i = array.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [array[i], array[j]] = [array[j], array[i]];
  }
}

// --- INIT ---
loadLeaderboard();
