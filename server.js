const express = require("express");
const path = require("path");

const app = express();
const PORT = 3000;

// Serve frontend files
app.use(express.static(__dirname));

app.listen(PORT, () => {
    console.log(`AU Merch server running at http://localhost:${PORT}`);
});