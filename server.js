const express = require("express");
const path = require("path");
const fs = require("fs");

const app = express();
const PORT = 3000;

// Parse JSON data
app.use(express.json());

// Parse form data
app.use(express.urlencoded({ extended: true }));

// Serve frontend files
app.use(express.static(__dirname));

// Login
app.post("/api/login", (req, res) => {
    const { student_id, password } = req.body;

    const users = JSON.parse(
        fs.readFileSync(
            path.join(__dirname, "data", "users.json"),
            "utf8"
        )
    );

    const user = users.find(
        user =>
            user.student_id === student_id &&
            user.password === password
    );

    if (!user) {
        return res.status(401).json({
            success: false,
            message: "Invalid Student ID or password"
        });
    }

    res.json({
        success: true,
        message: "Login successful",
        user: {
            id: user.id,
            student_id: user.student_id,
            name: user.name,
            email: user.email
        }
    });
});

app.listen(PORT, () => {
    console.log(`AU Merch server running at http://localhost:${PORT}`);
});