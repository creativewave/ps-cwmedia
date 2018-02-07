
/* global window */
/* global $ */
/* global cwmedia */
/* global display_multishop_checkboxes */
/* global ProductMultishop */

import flow    from 'lodash/flow'
import partial from 'lodash/partial'

/**
 * Initializes UI.
 * Called by `tabs_manager` (global object) when product tab is loaded.
 */
const init = () => {

    const backendData = JSON.parse(cwmedia)

    const mediaTableId        = 'media-list'
    const openLibraryButtonId = 'open-library'
    const openLinkButtonId    = 'open-link'
    const uploadBtnId         = 'cwmedia-selectbutton'

    const $mediaTable   = document.getElementById(mediaTableId)
    const $openLibrary  = document.getElementById(openLibraryButtonId)
    const $openLink     = document.getElementById(openLinkButtonId)
    const $setMultishop = document.querySelector('[name="multishop_check[cwmedia]"]')

    const $modalWrapper = document.createElement('div')

    let originalOrder = false

    // Init drag and drop (this is the only dependency to jQuery).
    $(`#${mediaTableId}`).tableDnD({
        onDragStart: () => originalOrder = $.tableDnD.serialize(),
        onDrop: table => {
            if (originalOrder == $.tableDnD.serialize()) {
                return
            }
            setMediaPositions(table)
        },
    })
    const updateDnd = $(`#${mediaTableId}`).tableDnDUpdate.bind($(`#${mediaTableId}`))

    // Init open library and link buttons.
    $modalWrapper.classList.add('bootstrap')
    document.body.appendChild($modalWrapper)
    $openLibrary.addEventListener('click', async () => {

        const { add, entrypoint, library: { error, title } } = backendData

        let content
        let handler

        try {
            const excludedIds = getProductMediaIds($mediaTable)
            const mediaList   = await getMediaList(fetch, entrypoint, excludedIds)
            const getMedia    = partial(getSelectedMedia, mediaList)
            const addMedia    = partial(addMediaRows, $mediaTable)
            content = getMediaFields(mediaList)
            handler = flow(getMedia, addMedia, updateDnd)
        } catch (e) {
            content = `<p>${error}</p>`
        } finally {
            $modalWrapper.appendChild(getModalForm(document, { content, handler, add, title }))
        }
    })
    $openLink.addEventListener('click', () => {

        const { add, link: { error, title } } = backendData

        const content  = '<input type="text" name="media_url">'
        const addMedia = partial(addMediaRows, $mediaTable)
        const handler  = flow(partial(getLinkedMedia, error), addMedia, updateDnd)

        $modalWrapper.appendChild(getModalForm(document, { content, error, handler, add, title }))
    })

    // Init remove button.
    $mediaTable.addEventListener('click', flow(removeMediaRow, setMediaPositions))

    /**
     * Enable/disable UI interactions of each fields.
     * Called when tab is loaded or on click on the multishop radio input.
     */
    if (display_multishop_checkboxes) { // eslint-disable-line camelcase

        const getRemoveBtnIds = partial(getElementsIds, document, '[id^=remove-id-media]')
        const btnIds          = [openLibraryButtonId, openLinkButtonId, uploadBtnId]
        const checkEachFields = partial(checkFields, ProductMultishop.checkField, $setMultishop)
        const checkAllFields  = flow(getRemoveBtnIds, [].concat.bind(btnIds), checkEachFields)

        ProductMultishop.checkAllModuleCwmedia = checkAllFields
        ProductMultishop.checkAllModuleCwmedia()
        $setMultishop.addEventListener('change', ProductMultishop.checkAllModuleCwmedia)
    }
}

const addMediaRows = ($table, media) =>
    /* eslint-disable camelcase */
    media.map(({ content, height, id_media, type, src, width }) => {

        if (!content) {
            return false
        }

        const $row = $table.tBodies[0].insertRow() // Side effect
        const position = $table.tBodies[0].rows.length

        $row.id = `id-media-${id_media || content}`
        $row.innerHTML = `
            <td class="hidden">
                <input type="hidden" name="media[${position}][id_media]" value="${id_media || 0}">
                <input type="hidden" name="media[${position}][type]" value="${type}">
                <input type="hidden" name="media[${position}][content]" value="${content}">
            </td>
            <td><img src="${src}" alt="${content}" class="img-thumbnail" width="${width}" height="${height}"></td>
            <td id="position-id-media-${id_media || content}" class="center">${position}</td>
            <td>
                <button type="button" id="remove-id-media-${id_media || content}" class="btn btn-default">
                    <i class="icon-trash text-danger"></i>
                </button>
            </td>
        `
    })
    /* eslint-enable camelcase */

const removeMediaRow = ({ currentTarget, target }) => {

    const $button  = 'I' === target.tagName ? target.parentNode : target

    if (!$button.id.startsWith('remove-id-media')) {
        return
    }

    let $tr = $button.parentNode
    while ('TR' !== $tr.tagName) {
        $tr = $tr.parentNode
    }
    $tr.remove()

    return currentTarget
}

const setMediaPositions = $table => {
    const [{ rows }] = $table.tBodies
    for (let index = 0, count = rows.length; index < count; index++) {
        rows[index].querySelector('td[id^=position-id-media]').innerHTML = index + 1
        for (const input of rows[index].getElementsByTagName('input')) {
            // Side effect
            input.name = input.name.substr(0, 'media['.length) + (index + 1) + input.name.substr('media['.length + 1)
        }
    }
}

const getProductMediaIds = $table =>
    [...$table.querySelectorAll('input[name$="[id_media]"]')].map(input => input.value)

const getMediaList = async (fetch, entrypoint, excluded) => {

    const url = `${entrypoint}&action=getMediaList&ajax=1&excluded[]=${excluded.join('&excluded[]=')}`
    const options = { credentials: 'same-origin' }
    const response = await fetch(url, options)
    const data = await response.json()

    return data.text
}

const getMediaFields = media =>
    `<ul class="library-list">
        ${media.map(({ content, height, src, width }, index) => `
            <li class="library-item">
                <label for="select-media-${index}">
                    <input type="checkbox" name="media" id="select-media-${index}" value="${index}">
                    <img
                        src="${src}"
                        width="${300}"
                        height="${(height * 300 / width).toFixed()}"
                        alt="${content}"
                        class="img-thumbnail"
                    >
                </label>
            </li>
        `).join('')}
    </ul>`

const getSelectedMedia = (media, e) => {

    e.preventDefault()

    const selectedMedia = []

    for (const { checked, value } of e.target.getElementsByTagName('input')) {
        checked && selectedMedia.push(media[value])
    }

    return selectedMedia
}

const getLinkedMedia = (error, e) => {

    e.preventDefault()

    const url = e.target.querySelector('[name=media_url]').value
    const [,,, content] = url.split(/((youtu\.be\/|v=)([0-9a-z_-]+))/i)

    if (!content) {
        throw Error(error)
    }

    return [{ type: 'video/youtube', content, src: `https://img.youtube.com/vi/${content}/default.jpg` }]
}

const getModalForm = (creator, { content, handler, add, title }) => {

    const $modal = creator.createElement('div')

    $modal.classList.add('modal')
    $modal.style.display = 'block' // Override default Bootstrap CSS.
    $modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" id='close-modal' class="close">&times;</button>
                    <h3>${title}</h3>
                </div>
                <form method="post">
                    <div class="modal-body">${content}</div>
                </form>
            </div>
        </div>
    `
    $modal.querySelector('#close-modal').addEventListener('click', $modal.remove.bind($modal))

    if (handler) {

        const [$form] = $modal.getElementsByTagName('form')

        $form.innerHTML += `
            <div class="modal-footer">
                <p class="alert alert-danger hidden"></p>
                <button class="btn btn-primary">${add}</button>
            </div>
        `
        $form.addEventListener('submit', event => {
            try {
                handler(event)
            } catch (error) {
                $form.lastElementChild.firstElementChild.innerHTML = error.message
                return $form.lastElementChild.firstElementChild.classList.remove('hidden')
            }
            $modal.remove()
        })
    }

    return $modal
}

const getElementsIds = (parent, selector) =>
    [...parent.querySelectorAll(selector)].map($el => $el.id)

const checkFields = (checkFn, $predicate, ids) =>
    ids.forEach(id => checkFn($predicate.checked, id))

window.CW = window.CW || {}
window.CW.Media = { init }
