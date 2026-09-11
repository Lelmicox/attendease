const menuToggle1 = document.getElementById('menu-toggle1'); // hamburger
const menuToggle2 = document.getElementById('menu-toggle2'); // close icon
const nav = document.querySelector('nav');


menuToggle1.addEventListener('click', () => {

    nav.classList.remove('closing');
    nav.classList.add('active');


    // Toggle icon visibility
    menuToggle1.style.display = 'none';
    menuToggle2.style.display = 'block';

    // Animate the incoming close icon
    menuToggle2.style.animation = 'fadeSlideIn 0.4s ease forwards';

});

menuToggle2.addEventListener('click', () => {
nav.classList.remove('active');
nav.classList.add('closing');

// Toggle icon visibility
    menuToggle2.style.display = 'none';
    menuToggle1.style.display = 'block';

    // Animate the incoming hamburger icon
    menuToggle1.style.animation = 'fadeSlideIn 0.4s ease forwards';

});


// Scroll listener for header hide/show
let lastScrollY = window.scrollY;

window.addEventListener('scroll', () => {
    if (window.scrollY > lastScrollY && window.scrollY > 10) {
        // scrolling down past 10px → hide header
        header.classList.add('hidden');
    } else {
        // scrolling up → show header
        header.classList.remove('hidden');
    }
    lastScrollY = window.scrollY;
});
// ===================================================
// this shit_code is for removing the nav if user click's on the 
// background if the nav appears
//======================================
const navBar = document.querySelector('.nav');
const sideBarOverlay = document.getElementById('sidebar-overlay');
const openBtn = document.getElementById('menu-toggle1');
const closeBtn = document.getElementById('menu-toggle2');

function openSidebar() {
  if (navBar && sideBarOverlay) {
    navBar.classList.add('active');
    sideBarOverlay.classList.add('active');
  }
}

function closeSidebar() {
  if (navBar && sideBarOverlay) {
    navBar.classList.remove('active');
    sideBarOverlay.classList.remove('active');
  }
}

// Open/Close triggers
if (openBtn) openBtn.addEventListener('click', openSidebar);
if (closeBtn) closeBtn.addEventListener('click', closeSidebar);

// Close when tapping overlay background
if (sideBarOverlay) {
  sideBarOverlay.addEventListener('click', () => {
    if (navBar.classList.contains('active')) {
      closeSidebar();
    }
  });
}


// ================================================
// generate course options based on selected department
// =========================================
document.getElementById('department_id').addEventListener('change', function () {
    let deptId = this.value;
    let courseSelect = document.getElementById('course_id');

    //  Clear existing options
    courseSelect.innerHTML = '<option value="">Select Course</option>';

    if (deptId) {
        spinner.style.display = 'inline';
        
        let loadingOpt = document.createElement('option');
        loadingOpt.textContent = "Fetching courses...";
        loadingOpt.disabled = true;
        loadingOpt.selected = true;
        courseSelect.appendChild(loadingOpt);


        fetch('fetch_courses.php?department_id=' + deptId)
            .then(response => response.json())
            .then(data => {
                courseSelect.innerHTML = '<option value="">Select Course</option>';
                if (data.length > 0) {
                    data.forEach(course => {
                        let opt = document.createElement('option');
                        opt.value = course.id;
                        opt.textContent = course.name;
                        courseSelect.appendChild(opt);
                    });

                }
                else {
                    let opt = document.createElement('option');
                    opt.value = "";
                    opt.textContent = "No course available!";
                    opt.disabled = true;
                    courseSelect.appendChild(opt);
                }
                spinner.style.display = 'none';

            })
            .catch(err => console.error('Error fetching courses:', err));
        spinner.style.display = 'none';
    }
});


