document.addEventListener('DOMContentLoaded', function() {
  async function copyTextToClipboard(textToCopy) {
    try {
      if (navigator?.clipboard?.writeText) {
        // Use Clipboard API if available
        await navigator.clipboard.writeText(textToCopy);
        console.log('Text copied to clipboard using Clipboard API:', textToCopy);
        showTooltip('Text copied to clipboard');
      } else if (document.execCommand) {
        // Fallback to document.execCommand('copy') method
        const textArea = document.createElement('textarea');
        textArea.value = textToCopy;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        console.log('Text copied to clipboard using document.execCommand:', textToCopy);
        showTooltip('Text copied to clipboard');
      } else {
        throw new Error('Clipboard API and document.execCommand are not supported in this browser.');
      }
    } catch (err) {
      console.error('Failed to copy text: ', err);
      showTooltip('Failed to copy text');
    }
  }

function showTooltip(message, button) {
  const tooltip = document.createElement('div');
  tooltip.className = 'tooltip-copy';
  tooltip.textContent = message;
  
  const buttonRect = button.getBoundingClientRect();
  tooltip.style.bottom = window.innerHeight - buttonRect.top + 'px';
  tooltip.style.left = buttonRect.left + buttonRect.width / 2 + 'px';
  
  document.body.appendChild(tooltip);
  setTimeout(() => {
    tooltip.remove();
  }, 500); // Remove the tooltip after 0.5 seconds
}

  // Get references to all elements with class "copyButton"
  const copyButtons = document.querySelectorAll('.copyButton');

  // Add event listeners to all copy buttons
copyButtons.forEach(function(button, index) {
  button.addEventListener('click', function() {
    const textToCopy = document.querySelectorAll('.textToCopy')[index].innerText;
    copyTextToClipboard(textToCopy);
    showTooltip('¡Copiado!', button);
  });
});
});