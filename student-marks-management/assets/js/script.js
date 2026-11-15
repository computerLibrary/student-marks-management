document.addEventListener('DOMContentLoaded', () => {
    const classSelect = document.getElementById('class_id');
    const subjectSelect = document.getElementById('subject_id');
    const maxInput = document.getElementById('max_score');
    const studentsArea = document.getElementById('students-area');

    if (subjectSelect && maxInput) {
        subjectSelect.addEventListener('change', () => {
            const option = subjectSelect.options[subjectSelect.selectedIndex];
            if (option && option.dataset.max) maxInput.value = option.dataset.max;
        });
    }

    if (classSelect && studentsArea) {
        classSelect.addEventListener('change', async () => {
            const classId = classSelect.value;
            studentsArea.innerHTML = '<p class="muted">Loading students...</p>';
            if (!classId) {
                studentsArea.innerHTML = '<p class="muted">Select a class to load its students.</p>';
                return;
            }
            try {
                const response = await fetch('/students/list_by_class.php?class_id=' + encodeURIComponent(classId));
                if (!response.ok) throw new Error('Could not load students.');
                const students = await response.json();
                if (!students.length) {
                    studentsArea.innerHTML = '<p class="muted">There are no students in this class yet. Register students first.</p>';
                    return;
                }
                const table = document.createElement('table');
                table.innerHTML = '<thead><tr><th>Admission number</th><th>Student name</th><th>Score</th></tr></thead>';
                const body = document.createElement('tbody');
                students.forEach(student => {
                    const tr = document.createElement('tr');
                    const adm = document.createElement('td'); adm.textContent = student.admission_no;
                    const name = document.createElement('td'); name.textContent = student.full_name;
                    const scoreCell = document.createElement('td');
                    const input = document.createElement('input');
                    input.type = 'number'; input.name = 'scores[' + student.id + ']';
                    input.min = '0'; input.step = '0.01'; input.placeholder = 'Enter score';
                    input.className = 'score-input';
                    scoreCell.appendChild(input);
                    tr.append(adm, name, scoreCell); body.appendChild(tr);
                });
                table.appendChild(body);
                studentsArea.replaceChildren();
                const heading = document.createElement('h2'); heading.textContent = 'Enter student scores';
                const note = document.createElement('p'); note.className = 'help'; note.textContent = 'Leave a score blank if the student was not assessed. Scores above the maximum mark will be skipped.';
                studentsArea.append(heading, note, table);
            } catch (error) {
                studentsArea.textContent = error.message;
            }
        });
    }
});
