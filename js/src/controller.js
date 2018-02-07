
const init = ({ target }) => {

    const state = {
        $content: target.querySelector('[name=content]'),
        $inputLink: target.querySelector('[name=link]'),
        $inputFile: target.querySelector('[name=media]'),
        $inputFileName: target.querySelector('[name="filename"]'),
        $typeId: target.querySelector('[name=id_type]'),
    }
    const updateForm = makeUpdateForm(state)

    state.$inputLink.addEventListener('change', updateForm)
    state.$inputFile.addEventListener('change', updateForm)
}

const makeUpdateForm = ({ $content, $inputLink, $inputFile, $inputFileName, $typeId }) => ({ target }) => {
    switch (target.name) {
        case 'link':
            $content.value = target.value.split(/((youtu\.be\/|v=)([0-9a-z_-]+))/i)[3]
            $inputFile.value = $inputFileName.value = ''
            $typeId.value = 1
            break
        default:
            $content.value = $inputFileName.value
            $inputLink.value = ''
            $typeId.value = 0
            break
    }
}

document.addEventListener('DOMContentLoaded', init)
