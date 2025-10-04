<!-- Modal para cambiar imagen de perfil -->
<div class="modal fade" id="profileImageModal" tabindex="-1" aria-labelledby="profileImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileImageModalLabel">Cambiar imagen de perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="profileImageForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="profile_image" class="form-label">Selecciona una nueva imagen</label>
                        <input class="form-control" type="file" id="profile_image" name="profile_image" accept="image/*" required>
                        <div class="form-text">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB.</div>
                    </div>
                    <div id="imagePreview" class="text-center my-3" style="display: none;">
                        <img src="" alt="Vista previa" class="img-fluid rounded" style="max-height: 200px;">
                    </div>
                    <div class="alert alert-danger" id="uploadError" style="display: none;"></div>
                    <div class="alert alert-success" id="uploadSuccess" style="display: none;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="uploadImageBtn">Subir imagen</button>
            </div>
        </div>
    </div>
</div>


