jQuery(function ($) {
  'use strict';

  const $root = $('.spm-admin');

  if (!$root.length) {
    return;
  }

  const adminText = window.spmAdmin || {};
  const editorId = 'spm_shared_description_editor';

  let rowIndex = $root.find('.spm-model-card').length;
  let activeDescriptionCard = null;

  function slugify(text) {
    return (text || '')
      .toString()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-');
  }

  function getEditorTextarea() {
    return $('#' + editorId);
  }

  function getEditorWrap() {
    return $('#wp-' + editorId + '-wrap');
  }

  function isTextModeActive() {
    const $wrap = getEditorWrap();

    if (!$wrap.length) {
      return false;
    }

    return $wrap.hasClass('html-active');
  }

  function syncVisibleTinyMceToTextarea() {
    if (typeof window.tinymce === 'undefined') {
      return;
    }

    const editor = window.tinymce.get(editorId);

    if (!editor) {
      return;
    }

    if (typeof editor.isHidden === 'function' && editor.isHidden()) {
      return;
    }

    editor.save();
  }

  function getEditorContent() {
    const $textarea = getEditorTextarea();

    if (!$textarea.length) {
      return '';
    }

    if (isTextModeActive()) {
      return $textarea.val() || '';
    }

    syncVisibleTinyMceToTextarea();

    return $textarea.val() || '';
  }

  function setEditorContent(content) {
    const normalized = content || '';
    const $textarea = getEditorTextarea();

    if ($textarea.length) {
      $textarea.val(normalized);
    }

    if (typeof window.tinymce !== 'undefined') {
      const editor = window.tinymce.get(editorId);

      if (editor) {
        editor.setContent(normalized);
        editor.save();
      }
    }
  }

  function hasParagraphMarkup(content) {
    return /<(p|br)\b/i.test(content || '');
  }

  function isInlineTag(tagName) {
    const inlineTags = new Set([
      'A', 'ABBR', 'ACRONYM', 'B', 'BDO', 'BIG', 'BUTTON', 'CITE', 'CODE', 'DFN',
      'EM', 'I', 'IMG', 'INPUT', 'KBD', 'LABEL', 'MAP', 'MARK', 'OBJECT', 'Q',
      'S', 'SAMP', 'SELECT', 'SMALL', 'SPAN', 'STRONG', 'SUB', 'SUP', 'TEXTAREA',
      'TIME', 'TT', 'U', 'VAR'
    ]);

    return inlineTags.has(tagName);
  }

  function normalizeMixedHtmlContent(content) {
    let normalized = (content || '').replace(/\r\n/g, '\n').trim();

    if (!normalized) {
      return '';
    }

    if (hasParagraphMarkup(normalized)) {
      return normalized;
    }

    const container = document.createElement('div');
    container.innerHTML = normalized;

    const output = [];
    let inlineBuffer = [];

    function flushInlineBuffer() {
      if (!inlineBuffer.length) {
        return;
      }

      const joined = inlineBuffer.join('').trim();
      inlineBuffer = [];

      if (!joined) {
        return;
      }

      const paragraphs = joined
        .split(/\n{2,}/)
        .map(function (part) {
          return part.trim();
        })
        .filter(Boolean);

      if (!paragraphs.length) {
        return;
      }

      paragraphs.forEach(function (paragraph) {
        output.push('<p>' + paragraph.replace(/\n/g, '<br />') + '</p>');
      });
    }

    Array.from(container.childNodes).forEach(function (node) {
      if (node.nodeType === Node.TEXT_NODE) {
        const text = (node.textContent || '').replace(/\u00a0/g, ' ');
        if (text.trim()) {
          inlineBuffer.push(text);
        } else if (text.indexOf('\n') !== -1) {
          inlineBuffer.push(text);
        }
        return;
      }

      if (node.nodeType !== Node.ELEMENT_NODE) {
        return;
      }

      const tagName = node.tagName.toUpperCase();

      if (tagName === 'BR') {
        inlineBuffer.push('<br />');
        return;
      }

      if (isInlineTag(tagName)) {
        inlineBuffer.push(node.outerHTML);
        return;
      }

      flushInlineBuffer();
      output.push(node.outerHTML);
    });

    flushInlineBuffer();

    const finalHtml = output.join('\n').trim();

    return finalHtml || normalized;
  }

  function normalizePlainTextareaContent(content) {
    return (content || '')
      .replace(/\r\n/g, '\n')
      .replace(/\r/g, '\n');
  }

  function getShortDescriptionInput($card) {
    return $card.find('.spm-short-description-input');
  }

  function getShortDescriptionHidden($card) {
    return $card.find('.spm-short-description-hidden');
  }

  function getShortDescriptionInputValue($card) {
    const $input = getShortDescriptionInput($card);
    return normalizePlainTextareaContent($input.val() || '');
  }

  function getShortDescriptionHiddenValue($card) {
    const $hidden = getShortDescriptionHidden($card);
    return normalizePlainTextareaContent($hidden.val() || '');
  }

  function updateRowIndexes() {
    $root.find('.spm-model-card').each(function (index) {
      const $card = $(this);

      $card.attr('data-row-index', index);
      $card.find('.spm-menu-order-field').val(index);
    });
  }

  function initSortable() {
    const $list = $root.find('.spm-models-list');

    if ($list.data('sortable-init')) {
      return;
    }

    $list.sortable({
      items: '.spm-model-card',
      handle: '.spm-model-card__drag',
      placeholder: 'spm-model-card--placeholder',
      update: function () {
        updateRowIndexes();
      }
    });

    $list.data('sortable-init', true);
  }

  function createCardHtml() {
    const templateHtml = $root.find('.spm-model-template').html();

    if (!templateHtml) {
      return '';
    }

    const currentIndex = rowIndex++;

    return templateHtml.replace(/__ROW_INDEX__/g, currentIndex);
  }

  function refreshCardHeader($card) {
    const name = $card.find('.spm-model-name').val() || adminText.addModelLabel || 'Novo modelo';
    const slug = $card.find('.spm-model-slug').val() || '';

    $card.find('.spm-model-card__title-text').text(name);
    $card.find('.spm-model-card__slug-preview').text(slug);
  }

  function getCardName($card) {
    return $card.find('.spm-model-name').val() || adminText.addModelLabel || 'Novo modelo';
  }

  function updateDescriptionSummary($card, content) {
    const plainText = $('<div>').html(content || '').text().trim();
    const label = plainText.length > 0
      ? (adminText.editorContentSummary || 'Conteúdo preenchido')
      : (adminText.editorContentEmpty || 'Sem descrição completa');

    const $text = $card.find('.spm-description-summary__text');
    $text.text(label);
    $text.removeClass('is-filled is-empty-desc');
    if (plainText.length > 0) {
      $text.addClass('is-filled');
    } else {
      $text.addClass('is-empty-desc');
    }
  }

  function showApplyFeedback($card) {
    let $feedback = $card.find('.spm-description-feedback');

    if (!$feedback.length) {
      $feedback = $('<div class="spm-description-feedback" />');
      $card.find('.spm-description-summary').after($feedback);
    }

    $feedback.stop(true, true)
      .text('Descrição aplicada com sucesso.')
      .addClass('is-visible')
      .fadeIn(150)
      .delay(1200)
      .fadeOut(250, function () {
        $(this).removeClass('is-visible');
      });
  }

  function applyDescriptionToCard($card, silent = false, normalize = false) {
    if (!$card || !$card.length) {
      return;
    }

    const rawContent = getEditorContent();
    const storedContent = normalize ? normalizeMixedHtmlContent(rawContent) : rawContent;

    $card.find('.spm-description-hidden').val(storedContent);
    updateDescriptionSummary($card, storedContent);

    if (normalize && storedContent !== rawContent) {
      setEditorContent(storedContent);
    }

    if (!silent) {
      showApplyFeedback($card);
    }
  }

  function syncActiveDescriptionCard(silent = true) {
    if (!activeDescriptionCard || !activeDescriptionCard.length) {
      return;
    }

    applyDescriptionToCard(activeDescriptionCard, silent, false);
  }

  function getShortDescriptionStatusLabels() {
    return {
      applied: adminText.shortDescriptionApplied || 'Breve descrição aplicada',
      empty: adminText.shortDescriptionEmpty || 'Sem descrição breve',
      pending: adminText.shortDescriptionPending || 'Alteração de descrição breve não aplicada',
      feedback: adminText.shortDescriptionFeedback || 'Descrição breve aplicada com sucesso.'
    };
  }

  function updateShortDescriptionStatus($card, state) {
    if (!$card || !$card.length) {
      return;
    }

    const labels = getShortDescriptionStatusLabels();
    const $status = $card.find('.spm-short-description-status__text');
    const currentValue = getShortDescriptionInputValue($card);
    const hiddenValue = getShortDescriptionHiddenValue($card);

    let status = state || '';

    if (!status) {
      if (currentValue !== hiddenValue) {
        status = 'pending';
      } else if (currentValue.trim().length) {
        status = 'applied';
      } else {
        status = 'empty';
      }
    }

    $status.removeClass('is-applied is-empty is-pending');

    if ('pending' === status) {
      $status.addClass('is-pending').text(labels.pending);
      return;
    }

    if ('empty' === status) {
      $status.addClass('is-empty').text(labels.empty);
      return;
    }

    $status.addClass('is-applied').text(labels.applied);
  }

  function showShortDescriptionFeedback($card) {
    const labels = getShortDescriptionStatusLabels();
    let $feedback = $card.find('.spm-short-description-feedback');

    if (!$feedback.length) {
      $feedback = $('<div class="spm-short-description-feedback" />');
      $card.find('.spm-short-description-tools').after($feedback);
    }

    $feedback.stop(true, true)
      .text(labels.feedback)
      .addClass('is-visible')
      .fadeIn(150)
      .delay(1200)
      .fadeOut(250, function () {
        $(this).removeClass('is-visible');
      });
  }

  function applyShortDescriptionToCard($card, silent = false) {
    if (!$card || !$card.length) {
      return;
    }

    const currentValue = getShortDescriptionInputValue($card);

    getShortDescriptionHidden($card).val(currentValue);
    updateShortDescriptionStatus($card, currentValue.trim().length ? 'applied' : 'empty');

    if (!silent) {
      showShortDescriptionFeedback($card);
    }
  }

  function syncAllShortDescriptions(silent = true) {
    $root.find('.spm-model-card').each(function () {
      applyShortDescriptionToCard($(this), silent);
    });
  }

  function selectDescriptionCard($card) {
    if (activeDescriptionCard && activeDescriptionCard.length && !$card.is(activeDescriptionCard)) {
      applyDescriptionToCard(activeDescriptionCard, true, true);
    }

    activeDescriptionCard = $card;

    $root.find('.spm-model-card').removeClass('is-editing-description');
    $card.addClass('is-editing-description');

    const name = getCardName($card);
    const rawContent = $card.find('.spm-description-hidden').val() || '';
    const displayContent = normalizeMixedHtmlContent(rawContent);

    $('.spm-selected-model-label').text((adminText.editorSelectedPrefix || 'Editando:') + ' ' + name);
    setEditorContent(displayContent);
  }

  function bindEditorSyncEvents() {
    const $textarea = getEditorTextarea();

    if ($textarea.length) {
      $textarea.off('.spmEditor').on('input.spmEditor keyup.spmEditor change.spmEditor', function () {
        if (isTextModeActive()) {
          syncActiveDescriptionCard(true);
        }
      });
    }

    function attachTinyMceEvents() {
      if (typeof window.tinymce === 'undefined') {
        return;
      }

      const editor = window.tinymce.get(editorId);

      if (!editor || editor._spmEventsBound) {
        return;
      }

      editor.on('keyup change NodeChange SetContent input undo redo', function () {
        syncActiveDescriptionCard(true);
      });

      editor._spmEventsBound = true;
    }

    $(document).off('tinymce-editor-init.spm').on('tinymce-editor-init.spm', function (event, editor) {
      if (!editor || editor.id !== editorId) {
        return;
      }

      attachTinyMceEvents();
    });

    attachTinyMceEvents();

    $('#' + editorId + '-tmce, #' + editorId + '-html')
      .off('click.spmEditorMode')
      .on('click.spmEditorMode', function () {
        window.setTimeout(function () {
          syncActiveDescriptionCard(true);
        }, 0);
      });
  }

  function bindCardEvents($context) {
    $context.find('.spm-model-name').off('input.spm').on('input.spm', function () {
      $(this).removeClass('has-error');

      const $card = $(this).closest('.spm-model-card');
      const $slug = $card.find('.spm-model-slug');

      if (!$slug.data('touched')) {
        $slug.val(slugify($(this).val()));
      }

      refreshCardHeader($card);

      if (activeDescriptionCard && $card.is(activeDescriptionCard)) {
        $('.spm-selected-model-label').text((adminText.editorSelectedPrefix || 'Editando:') + ' ' + getCardName($card));
      }
    });

    $context.find('.spm-model-slug').off('input.spm').on('input.spm', function () {
      $(this).removeClass('has-error');

      const $card = $(this).closest('.spm-model-card');

      $(this).data('touched', true);
      $(this).val(slugify($(this).val()));
      refreshCardHeader($card);
    });

    $context.find('.spm-short-description-input')
      .off('input.spmShort change.spmShort keyup.spmShort')
      .on('input.spmShort change.spmShort keyup.spmShort', function () {
        const $card = $(this).closest('.spm-model-card');
        const currentValue = getShortDescriptionInputValue($card);
        const hiddenValue = getShortDescriptionHiddenValue($card);

        if (currentValue !== hiddenValue) {
          updateShortDescriptionStatus($card, 'pending');
          return;
        }

        updateShortDescriptionStatus($card, currentValue.trim().length ? 'applied' : 'empty');
      });

    $context.find('.spm-apply-short-description').off('click.spm').on('click.spm', function (e) {
      e.preventDefault();

      const $card = $(this).closest('.spm-model-card');
      applyShortDescriptionToCard($card, false);
    });

    $context.find('.spm-edit-description').off('click.spm').on('click.spm', function (e) {
      e.preventDefault();

      const $card = $(this).closest('.spm-model-card');

      selectDescriptionCard($card);

      const $panel = $('.spm-description-editor-panel');

      if ($panel.length) {
        $('html, body').animate({
          scrollTop: $panel.offset().top - 80
        }, 200);
      }
    });

    $context.find('.spm-remove-model').off('click.spm').on('click.spm', function () {
      if (!window.confirm(adminText.removeConfirm || 'Remover este modelo?')) {
        return;
      }

      const $card = $(this).closest('.spm-model-card');

      if (activeDescriptionCard && $card.is(activeDescriptionCard)) {
        activeDescriptionCard = null;
        $('.spm-selected-model-label').text('Nenhum modelo selecionado.');
        setEditorContent('');
      }

      $card.remove();
      updateRowIndexes();
    });

    $context.find('.spm-upload-featured-image').off('click.spm').on('click.spm', function (e) {
      e.preventDefault();

      const $card = $(this).closest('.spm-model-card');
      const $input = $card.find('.spm-featured-image-id');
      const $preview = $card.find('.spm-media-preview');

      const frame = wp.media({
        title: adminText.mediaTitle || 'Selecionar imagem',
        button: { text: adminText.mediaButton || 'Usar imagem' },
        multiple: false
      });

      frame.on('select', function () {
        const attachment = frame.state().get('selection').first().toJSON();

        $input.val(attachment.id);
        $preview.html('<img src="' + attachment.url + '" alt="" />');
      });

      frame.open();
    });

    $context.find('.spm-remove-featured-image').off('click.spm').on('click.spm', function (e) {
      e.preventDefault();

      const $card = $(this).closest('.spm-model-card');

      $card.find('.spm-featured-image-id').val('');
      $card.find('.spm-media-preview').empty();
    });

    $context.find('.spm-upload-gallery').off('click.spm').on('click.spm', function (e) {
      e.preventDefault();

      const $card = $(this).closest('.spm-model-card');
      const $input = $card.find('.spm-gallery-image-ids');
      const $preview = $card.find('.spm-gallery-preview');

      const frame = wp.media({
        title: adminText.galleryTitle || 'Selecionar galeria',
        button: { text: adminText.galleryButton || 'Usar galeria' },
        multiple: true
      });

      frame.on('select', function () {
        const selection = frame.state().get('selection').toJSON();
        const ids = [];
        let html = '';

        selection.forEach(function (item) {
          ids.push(item.id);
          html += '<img src="' + item.url + '" alt="" />';
        });

        $input.val(ids.join(','));
        $preview.html(html);
      });

      frame.open();
    });

    $context.find('.spm-remove-gallery').off('click.spm').on('click.spm', function (e) {
      e.preventDefault();

      const $card = $(this).closest('.spm-model-card');

      $card.find('.spm-gallery-image-ids').val('');
      $card.find('.spm-gallery-preview').empty();
    });
  }

  $('.spm-apply-description').off('click.spm').on('click.spm', function (e) {
    e.preventDefault();

    if (!activeDescriptionCard || !activeDescriptionCard.length) {
      return;
    }

    applyDescriptionToCard(activeDescriptionCard, false, true);
  });

  function validateRequiredFields() {
    const emptyCards = [];

    $root.find('.spm-model-card').not('.spm-model-template .spm-model-card').each(function () {
      const $card = $(this);
      const name = ($card.find('.spm-model-name').val() || '').trim();

      if (!name) {
        emptyCards.push($card);
      }
    });

    return emptyCards;
  }

  function validateUniqueSlugs() {
    const slugMap = {};

    $root.find('.spm-model-card').not('.spm-model-template .spm-model-card').each(function () {
      const $card = $(this);
      const slug = slugify($card.find('.spm-model-slug').val() || '');

      if (!slug) {
        return;
      }

      if (!slugMap[slug]) {
        slugMap[slug] = [];
      }

      slugMap[slug].push($card);
    });

    const duplicateCards = [];

    Object.keys(slugMap).forEach(function (slug) {
      if (slugMap[slug].length > 1) {
        slugMap[slug].forEach(function ($card) {
          duplicateCards.push($card);
        });
      }
    });

    return duplicateCards;
  }

  $('#post').off('submit.spm').on('submit.spm', function (e) {
    const $cards = $root.find('.spm-model-card').not('.spm-model-template .spm-model-card');

    if (!$cards.length) {
      return;
    }

    const emptyNameCards = validateRequiredFields();

    if (emptyNameCards.length) {
      e.preventDefault();
      emptyNameCards.forEach(function ($card) {
        $card.find('.spm-model-name').addClass('has-error');
      });
      $('html, body').animate({ scrollTop: emptyNameCards[0].offset().top - 80 }, 200);
      window.alert('Preencha o nome de todos os modelos antes de salvar.');
      return;
    }

    const duplicateCards = validateUniqueSlugs();

    if (duplicateCards.length) {
      e.preventDefault();
      duplicateCards.forEach(function ($card) {
        $card.find('.spm-model-slug').addClass('has-error');
      });
      $('html, body').animate({ scrollTop: duplicateCards[0].offset().top - 80 }, 200);
      window.alert('Existem modelos com slugs duplicados. Corrija antes de salvar.');
      return;
    }

    syncVisibleTinyMceToTextarea();

    if (activeDescriptionCard && activeDescriptionCard.length) {
      applyDescriptionToCard(activeDescriptionCard, true, true);
    }

    syncAllShortDescriptions(true);
  });

  $root.find('.spm-add-model').off('click.spm').on('click.spm', function (e) {
    e.preventDefault();

    const html = createCardHtml();

    if (!html) {
      console.error('Sica Product Models: template HTML do card não encontrado.');
      return;
    }

    const $card = $(html);

    $root.find('.spm-models-list').append($card);

    bindCardEvents($card);
    updateRowIndexes();
    refreshCardHeader($card);
    updateShortDescriptionStatus($card, 'empty');
    selectDescriptionCard($card);
  });

  bindCardEvents($root);
  bindEditorSyncEvents();
  initSortable();
  updateRowIndexes();

  $root.find('.spm-model-card').each(function () {
    const $card = $(this);
    refreshCardHeader($card);
    updateShortDescriptionStatus($card);
    updateDescriptionSummary($card, $card.find('.spm-description-hidden').val() || '');
  });

  if ($root.find('.spm-model-card').length) {
    $('.spm-selected-model-label').text(adminText.editorEmptyState || 'Selecione um modelo para editar a descrição completa.');
  }
});