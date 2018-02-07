
/*global $*/

const init = () => {
    $('[rel=cwmedia]').fancybox()
    $('#cwmedia-list')
        .css('overflow-x', 'hidden')
        .serialScroll({
            items: 'li',
            prev: '#cwmedia > button:first-child',
            next: '#cwmedia > button:last-child',
            cycle: false,
            onBefore: togglePrevNext,
        })
        .trigger('goto', [0])
}

const togglePrevNext = (...serialScrollParams) => {

    const [event, $item, $list, items, position] = serialScrollParams
    let displayedItemsLength = 0

    items.map(i => position <= i && (displayedItemsLength += items[i].offsetWidth))

    const hasNext = displayedItemsLength <= $list.outerWidth(true) || position === items.length
    const hasPrev = 0 === position

    $list.prev()
        .css('cursor', hasPrev ? 'default' : 'pointer')
        .css('display', hasPrev ? 'none' : 'block')
        .fadeTo(0, hasPrev ? 0 : 1)
    $list.next()
        .css('cursor', hasNext ? 'default' : 'pointer')
        .css('display', hasNext ? 'none' : 'block')
        .fadeTo(0, hasNext ? 0 : 1)
}

document.addEventListener('DOMContentLoaded', init)
