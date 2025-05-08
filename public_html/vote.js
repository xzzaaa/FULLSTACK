document.addEventListener("DOMContentLoaded", function() {
    const voteButtons = document.querySelectorAll(".vote-btn");

    voteButtons.forEach(button => {
        button.addEventListener("click", async function() {
            const commentId = this.dataset.commentId;
            const voteType = this.dataset.voteType;

            try {
                const response = await fetch("vote_comment.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `comment_id=${commentId}&vote=${voteType}`,
                    credentials: "include" 
                });

                if (!response.ok) {
                    throw new Error("Network response was not ok");
                }

                const result = await response.json();
                console.log(result);

                if (result.status === "success") {
                    document.getElementById(`upvotes-${commentId}`).innerText = result.upvotes;
                    document.getElementById(`downvotes-${commentId}`).innerText = result.downvotes;
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error("Fetch error:", error);
                alert("Något gick fel vid röstning. Kontrollera att du är inloggad och försök igen.");
            }
        });
    });
});
