(function() {
    const addUpgradeItemHighlight = function() {
        const submenuItem = document.querySelector( '.userfeedback-upgrade-submenu' );

        if ( submenuItem ) {
            const li = submenuItem.parentNode.parentNode;

            if ( li ) {
                li.classList.add( 'userfeedback-submenu-highlight' );
            }

            const parentLink = submenuItem.closest('a');
            parentLink.setAttribute('target', '_blank');
        }
    }

    addUpgradeItemHighlight();
})()