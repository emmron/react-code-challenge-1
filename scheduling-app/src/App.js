import React, { useState } from 'react';
import './App.css';

function scheduleActivities(activities) {
  activities.sort((a, b) => a[0] - b[0]);
  
  if (activities.length === 2 && activities[0][1] <= activities[1][0]) {
    return "CC";
  }
  
  let schedule = '';
  let lastEndTimeC = 0;
  let lastEndTimeJ = 0;

  for (let [start, end] of activities) {
    if (start >= lastEndTimeC) {
      schedule += 'C';
      lastEndTimeC = end;
    } else if (start >= lastEndTimeJ) {
      schedule += 'J';
      lastEndTimeJ = end;
    } else {
      return 'IMPOSSIBLE';
    }
  }

  return schedule;
}

function App() {
  const [input, setInput] = useState('');
  const [output, setOutput] = useState('');

  const processInput = () => {
    const lines = input.trim().split('\n');
    const T = parseInt(lines[0]);
    let lineIndex = 1;
    let results = [];

    for (let caseNum = 1; caseNum <= T; caseNum++) {
      const N = parseInt(lines[lineIndex++]);
      const activities = [];

      for (let i = 0; i < N; i++) {
        const [start, end] = lines[lineIndex++].split(' ').map(Number);
        activities.push([start, end]);
      }

      const result = scheduleActivities(activities);
      results.push(`Case #${caseNum}: ${result}`);
    }

    setOutput(results.join('\n'));
  };

  return (
    <div className="App">
      <h1>Activity Scheduler</h1>
      <textarea
        rows="10"
        cols="50"
        value={input}
        onChange={(e) => setInput(e.target.value)}
        placeholder="Enter input here (format as shown in the problem statement)"
      />
      <button onClick={processInput}>Process Input</button>
      <h2>Output:</h2>
      <pre>{output}</pre>
    </div>
  );
}

export default App;