        </main>

        <footer class="bg-dark text-white py-4 mt-4">
            <div class="container">
                <div class="row">
                    <div class="col-md-8">
                        <h5>PetMatch</h5>
                        <p>Conectando mascotas con familias amorosas este 2025 unete a nosotros.</p>
                    </div>
                </div>
                <hr>
                <div class="text-center">
                    <p>&copy; 2025 PetMatch. Todos los derechos reservados.</p>
                </div>
            </div>
        </footer>
        <?php if (isset($additionalScripts)): ?>
            <?php foreach ($additionalScripts as $script): ?>
                <script src="<?php echo BASE_URL; ?>assets/js/<?php echo $script; ?>"></script>
            <?php endforeach; ?>
        <?php endif; ?>
    </body>
</html>