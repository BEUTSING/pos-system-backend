<?php
namespace App\Service\Product;

use App\Entity\Product\Product;
use App\Entity\Product\ProductMedia;
use App\Repository\Product\CategoryRepository;
use App\Repository\Product\ProductMediaRepository;
use App\Repository\Product\ProductRepository;
use App\Repository\Stock\SupplierRepository;
use App\Service\Company\CompanyService;
use App\Service\Media\FileUploadService;

use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

class ProductService
{
    private $em;
    private $categoryrepository;
    private $supplierrepository;
    private $logEntryService;
    private $security;
    private $ProductRepository;
    private $companyService;
    private $mediaRepository;
    private $fileUploadService;

    public function __construct(
        EntityManagerInterface $em,
        CategoryRepository $categoryrepository,
        SupplierRepository $supplierrepository,
        Security $security,
        ProductRepository $repo,
        CompanyService $companyService,  // ← injected
        ProductMediaRepository $mediaRepository,
        FileUploadService $fileUploadService
    ) {
        $this->security = $security;
        $this->ProductRepository = $repo;
        $this->em = $em;
        $this->categoryrepository = $categoryrepository;
        $this->supplierrepository = $supplierrepository;
        $this->companyService = $companyService;
        $this->mediaRepository = $mediaRepository;
        $this->fileUploadService = $fileUploadService;
    }

    // ─── SEARCH: search products by name within the current company ──────────────
    public function searchP(string $pname): array
    {
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            throw new RuntimeException('No company assigned to the authenticated user');
        }
        $products = $this->ProductRepository->createQueryBuilder('p')
            ->where('p.company = :company')
            ->andWhere('p.productname LIKE :name')
            ->setParameter('company', $company)
            ->setParameter('name', '%' . $pname . '%')
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
        if (!$products) {
            throw new \InvalidArgumentException('Product not found');
        }

        $data = [];
        foreach ($products as $product) {
            $data[] = $this->formatProduct($product);
        }

        return $data;
    }

    // ─── LIST: retrieve all products belonging to the current company ────────────
    public function listP(): array
    {
        // Automatically retrieve the current company of the authenticated user
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            throw new RuntimeException('No company assigned to the authenticated user');
        }

        // Filter products by the current company only — not findAll()
        $products = $this->ProductRepository->findBy(['company' => $company]);

        $data = [];
        foreach ($products as $product) {
            $data[] = $this->formatProduct($product);
        }

        return $data;
    }

    // ─── CREATE: create a new product and automatically assign it to the current company ──
    public function createP(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        // Automatically retrieve the current company of the authenticated user
        $company = $this->companyService->getCurrentCompany();
        if (!$company) {
            throw new RuntimeException('No company assigned to the authenticated user');
        }

        // Validate required fields
        $required = ['productname', 'saleprice', 'purchaseprice', 'quantity', 'minimumstock'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("The field $field is required");
            }
        }

        $category = !empty($data['category']) ? $this->categoryrepository->find($data['category']) : null;
        if (!empty($data['category']) && (!$category || $category->getCompany()?->getId() !== $company->getId())) {
            throw new \InvalidArgumentException('Category not found in the active company');
        }

        $supplier = !empty($data['supplier']) ? $this->supplierrepository->find($data['supplier']) : null;
        if ($supplier && $supplier->getCompany()?->getId() !== $company->getId()) {
            throw new \InvalidArgumentException('Supplier not found in the active company');
        }

        $product = new Product();
        $product->setProductname($data['productname']);
        $product->setReference($data['reference'] ?? null);
        $product->setDescription($data['description'] ?? null);
        $product->setUnit($data['unit'] ?? 'pcs');
        $product->setCategory($category);
        $product->setSupplier($supplier);
        $product->setSaleprice($data['saleprice']);
        $product->setPurchaseprice($data['purchaseprice']);
        $product->setQuantity($data['quantity']);
        $product->setMinimumstock($data['minimumstock']);
        $product->setCompany($company); // ← automatically assigned from the current session

        $this->em->persist($product);
        $this->em->flush();

         

        return [[
            'id'            => $product->getId(),
            'productname'   => $product->getProductname(),
            'reference'     => $product->getReference(),
            'description'   => $product->getDescription(),
            'unit'          => $product->getUnit(),
            'category'      => $product->getCategory() ? [
                'id'          => $product->getCategory()->getId(),
                'name'        => $product->getCategory()->getCategoryname(),
                'description' => $product->getCategory()->getDescription(),
            ] : null,
            'supplier'      => $product->getSupplier() ? [
                'id'   => $product->getSupplier()->getId(),
                'name' => $product->getSupplier()->getName(),
            ] : null,
            'saleprice'     => (float) $product->getSaleprice(),
            'purchaseprice' => (float) $product->getPurchaseprice(),
            'quantity'      => $product->getQuantity(),
            'minimumstock'  => $product->getMinimumstock(),
            'company'       => $company->getNameComp(),
            'created_at'    => $product->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at'    => $product->getUpdatedAt()->format('Y-m-d H:i:s'),
            'picture'       => null,
            'pictures'      => [],
            'media'         => [],
        ]];
    }

    // ─── UPDATE: update an existing product ─────────────────────────────────────
    public function updateP(int $id, Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        $company = $this->companyService->getCurrentCompany();
        $product = $this->ProductRepository->find($id);
        if (!$company || !$product || $product->getCompany()?->getId() !== $company->getId()) {
            throw new \InvalidArgumentException('Product not found');
        }

        if (array_key_exists('category', $data)) {
            if ($data['category'] === null || $data['category'] === '') {
                $product->setCategory(null);
            } else {
                $category = $this->categoryrepository->find($data['category']);
                if (!$category || $category->getCompany()?->getId() !== $company->getId()) {
                    throw new \InvalidArgumentException('Category not found');
                }
                $product->setCategory($category);
            }
        }

        if (array_key_exists('supplier', $data)) {
            $supplier = $data['supplier'] ? $this->supplierrepository->find($data['supplier']) : null;
            if ($data['supplier'] && (!$supplier || $supplier->getCompany()?->getId() !== $company->getId())) {
                throw new \InvalidArgumentException('Supplier not found');
            }
            $product->setSupplier($supplier);
        }

        if (isset($data['saleprice']) && $data['saleprice'] < 0) {
            throw new \InvalidArgumentException('Sale price must be a positive number');
        }
        if (isset($data['purchaseprice']) && $data['purchaseprice'] < 0) {
            throw new \InvalidArgumentException('Purchase price must be a positive number');
        }

        if (isset($data['productname']))   $product->setProductname($data['productname']);
        if (array_key_exists('reference', $data)) $product->setReference($data['reference']);
        if (array_key_exists('description', $data)) $product->setDescription($data['description']);
        if (isset($data['unit'])) $product->setUnit($data['unit']);
        if (isset($data['quantity']))      $product->setQuantity($data['quantity']);
        if (isset($data['minimumstock']))  $product->setMinimumstock($data['minimumstock']);
        if (isset($data['saleprice']))     $product->setSaleprice($data['saleprice']);
        if (isset($data['purchaseprice'])) $product->setPurchaseprice($data['purchaseprice']);

        $this->em->flush();

        return ['message' => 'Product updated successfully'];
    }

    // ─── DELETE: remove a product by ID ─────────────────────────────────────────
    public function deleteP(int $id): string
    {
        $company = $this->companyService->getCurrentCompany();
        $product = $this->ProductRepository->find($id);
        if (!$company || !$product || $product->getCompany()?->getId() !== $company->getId()) {
            throw new \InvalidArgumentException('Product not found');
        }

        foreach ($this->mediaRepository->findForProduct((int) $product->getId()) as $media) {
            $this->fileUploadService->delete($media->getPath());
        }

        $this->em->remove($product);
        $this->em->flush();

        return 'Product deleted successfully';
    }

    // ─── HELPER: format a product as array ──────────────────────────────────────
    private function formatProduct(Product $product): array
    {
        return [
            'id'            => $product->getId(),
            'productname'   => $product->getProductname(),
            'reference'     => $product->getReference(),
            'description'   => $product->getDescription(),
            'unit'          => $product->getUnit(),
            'category'      => $product->getCategory()?->getCategoryname(),
            'supplier'      => $product->getSupplier()?->getName(),
            'saleprice'     => $product->getSaleprice(),
            'purchaseprice' => $product->getPurchaseprice(),
            'quantity'      => $product->getQuantity(),
            'minimumstock'  => $product->getMinimumstock(),
            'company'       => $product->getCompany()?->getNameComp(),
            'picture'       => $this->fileUploadService->publicUrl($product->getPicture()),
            'pictures'      => array_map(
                fn (string $path): ?string => $this->fileUploadService->publicUrl($path),
                $product->getPictures()
            ),
            'media'         => array_map(
                fn (ProductMedia $media): array => $this->formatMedia($media),
                $this->mediaRepository->findForProduct((int) $product->getId())
            ),
        ];
    }

    // ─── MEDIA: list, add, delete and promote product images ──────────────────────
    public function listMedia(int $productId): array
    {
        $product = $this->findProductInCurrentCompany($productId);

        return array_map(
            fn (ProductMedia $media): array => $this->formatMedia($media),
            $this->mediaRepository->findForProduct((int) $product->getId())
        );
    }

    public function addMedia(int $productId, Request $request): array
    {
        $product = $this->findProductInCurrentCompany($productId);
        $file = $request->files->get('image') ?? $request->files->get('file');
        if (!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            throw new \InvalidArgumentException('An image file is required in the "image" field');
        }

        $stored = $this->fileUploadService->store($file, 'products');
        $existing = $this->mediaRepository->findForProduct((int) $product->getId());

        $media = new ProductMedia();
        $media->setProduct($product);
        $media->setPath($stored['path']);
        $media->setPosition(count($existing));
        $media->setIsPrimary($existing === []);

        $this->em->persist($media);
        $this->em->flush();

        return array_map(
            fn (ProductMedia $item): array => $this->formatMedia($item),
            $this->mediaRepository->findForProduct((int) $product->getId())
        );
    }

    public function deleteMedia(int $productId, int $mediaId): array
    {
        $product = $this->findProductInCurrentCompany($productId);
        $media = $this->mediaRepository->find($mediaId);
        if (!$media || $media->getProduct()?->getId() !== $product->getId()) {
            throw new \InvalidArgumentException('Image not found for this product');
        }

        $wasPrimary = $media->isPrimary();
        $this->fileUploadService->delete($media->getPath());
        $this->em->remove($media);
        $this->em->flush();

        if ($wasPrimary) {
            $remaining = $this->mediaRepository->findForProduct((int) $product->getId());
            if ($remaining !== []) {
                $remaining[0]->setIsPrimary(true);
                $this->em->flush();
            }
        }

        return array_map(
            fn (ProductMedia $item): array => $this->formatMedia($item),
            $this->mediaRepository->findForProduct((int) $product->getId())
        );
    }

    public function setPrimaryMedia(int $productId, int $mediaId): array
    {
        $product = $this->findProductInCurrentCompany($productId);
        $target = $this->mediaRepository->find($mediaId);
        if (!$target || $target->getProduct()?->getId() !== $product->getId()) {
            throw new \InvalidArgumentException('Image not found for this product');
        }

        foreach ($this->mediaRepository->findForProduct((int) $product->getId()) as $media) {
            $media->setIsPrimary($media->getId() === $target->getId());
        }
        $this->em->flush();

        return array_map(
            fn (ProductMedia $item): array => $this->formatMedia($item),
            $this->mediaRepository->findForProduct((int) $product->getId())
        );
    }

    private function findProductInCurrentCompany(int $productId): Product
    {
        $company = $this->companyService->getCurrentCompany();
        $product = $this->ProductRepository->find($productId);
        if (!$company || !$product || $product->getCompany()?->getId() !== $company->getId()) {
            throw new \InvalidArgumentException('Product not found');
        }

        return $product;
    }

    private function formatMedia(ProductMedia $media): array
    {
        return [
            'id' => $media->getId(),
            'url' => $this->fileUploadService->publicUrl($media->getPath()),
            'path' => $media->getPath(),
            'is_primary' => $media->isPrimary(),
            'position' => $media->getPosition(),
        ];
    }
}