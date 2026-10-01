    </main>
    <script>
        const menuBtn = document.querySelector(".menu-btn");
        const closeBtn = document.querySelector(".close-btn");
        const sidebar = document.querySelector("aside");
        menuBtn.addEventListener("click", () => sidebar.classList.add("open"));
        closeBtn.addEventListener("click", () => sidebar.classList.remove("open"));
        document.addEventListener("click", (ev) => {
            if (sidebar.classList.contains("open") && !sidebar.contains(ev.target) && !menuBtn.contains(ev.target)) {
                sidebar.classList.remove("open");
            }
        });
    </script>
</body>
</html>
